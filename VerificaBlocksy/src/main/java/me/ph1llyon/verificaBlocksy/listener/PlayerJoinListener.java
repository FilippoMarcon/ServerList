package me.ph1llyon.verificaBlocksy.listener;

import me.ph1llyon.verificaBlocksy.VerificaBlocksy;
import me.ph1llyon.verificaBlocksy.http.WebApiClient;
import net.kyori.adventure.text.Component;
import org.bukkit.Bukkit;
import org.bukkit.entity.Player;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.player.PlayerJoinEvent;

import java.security.SecureRandom;
import java.nio.charset.StandardCharsets;
import java.util.UUID;

public class PlayerJoinListener implements Listener {
    private final VerificaBlocksy plugin;
    private final WebApiClient apiClient;
    private final SecureRandom random = new SecureRandom();

    public PlayerJoinListener(VerificaBlocksy plugin, WebApiClient apiClient) {
        this.plugin = plugin;
        this.apiClient = apiClient;
    }

    @EventHandler
    public void onPlayerJoin(PlayerJoinEvent event) {
        final Player player = event.getPlayer();

        Bukkit.getScheduler().runTaskAsynchronously(plugin, () -> {
            final String url = plugin.getConfig().getString("site_verification_url", "https://example.com/verifica-nickname");
            final String ticketUrl = plugin.getConfig().getString("support_ticket_url", "https://example.com/support");

            // 1) Controlla Premium/SP: se il server è offline-mode, considera SP; altrimenti usa Mojang
            boolean serverOnlineMode = plugin.getServer().getOnlineMode();
            boolean useServerGate = plugin.getConfig().getBoolean("use_server_online_mode_gate", false);
            boolean trustProxy = plugin.getConfig().getBoolean("trust_proxy_forwarding", false);

            // Determinazione Premium/SP
            boolean isPremium;
            if (!serverOnlineMode && trustProxy) {
                // Backend offline-mode ma proxy autentica: usa UUID inoltrato
                UUID offlineUuid = UUID.nameUUIDFromBytes(("OfflinePlayer:" + player.getName()).getBytes(StandardCharsets.UTF_8));
                isPremium = !player.getUniqueId().equals(offlineUuid);
            } else {
                // Modalità normale: usa Mojang API e opzionale gate
                isPremium = apiClient.isPremium(player.getName());
                if (useServerGate && !serverOnlineMode) {
                    isPremium = false;
                }
            }

            final String msg;
            if (!isPremium) {
                // SP/cracked: kick con istruzioni
                String tmplSp = plugin.getConfig().getString("sp_kick_message_template",
                        "Questo account non è Premium.\n- Acquista un account su https://www.minecraft.net/\n- Oppure apri un ticket: {ticket_url}\nScrivi che vuoi verificarti e che possiedi un account non comprato (SP).");
                msg = tmplSp.replace("{ticket_url}", ticketUrl);
            } else {
                // 2) Premium: flusso normale verifica
                boolean alreadyVerified = apiClient.isPlayerVerified(player.getName());
                if (alreadyVerified) {
                    String tmpl = plugin.getConfig().getString("already_verified_kick_message_template",
                            "Questo account è già verificato.\nVisita: {url}\nPer scollegare l'account.");
                    msg = tmpl.replace("{url}", url);
                } else {
                    // Genera il codice (lunghezza da config)
                    int len = plugin.getConfig().getInt("code_length", 6);
                    final String code = generateNumericCode(len);

                    // Registra il codice
                    boolean ok = apiClient.registerVerificationCode(player.getName(), code);

                    String tmpl = plugin.getConfig().getString("kick_message_template",
                            "Collega il tuo account sul sito:\n{url}\nCodice: {code}\nValido 5 minuti.");
                    msg = tmpl.replace("{code}", code).replace("{url}", url);
                }
            }

            // Kick sul main thread
            Bukkit.getScheduler().runTask(plugin, () -> {
                try {
                    player.kick(Component.text(msg));
                } catch (Throwable t) {
                    player.kickPlayer(msg);
                }
            });
        });
    }

    private String generateNumericCode(int len) {
        String digits = "0123456789";
        StringBuilder sb = new StringBuilder(len);
        for (int i = 0; i < len; i++) {
            sb.append(digits.charAt(random.nextInt(digits.length())));
        }
        return sb.toString();
    }
}