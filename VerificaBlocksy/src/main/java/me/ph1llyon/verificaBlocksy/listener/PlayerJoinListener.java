package me.ph1llyon.verificaBlocksy.listener;

import me.ph1llyon.verificaBlocksy.VerificaBlocksy;
import me.ph1llyon.verificaBlocksy.http.WebApiClient;
import net.kyori.adventure.text.Component;
import net.kyori.adventure.text.format.NamedTextColor;
import net.kyori.adventure.text.format.TextDecoration;
import net.kyori.adventure.text.event.ClickEvent;
import net.kyori.adventure.text.event.HoverEvent;
import net.kyori.adventure.text.serializer.gson.GsonComponentSerializer;
import org.bukkit.Bukkit;
import org.bukkit.entity.Player;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.player.PlayerJoinEvent;

import com.google.common.io.ByteStreams;

import java.io.ByteArrayOutputStream;
import java.io.DataOutputStream;
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

            final Component kickMsg;
            final String msgPlain;
            if (!isPremium) {
                // SP/cracked: messaggio pulito e stilizzato con link cliccabili
                kickMsg = buildSpKickMessage(ticketUrl);
                msgPlain = "Questo account non è Premium.\n- Acquista un account su https://www.minecraft.net/\n- Oppure apri un ticket: " + ticketUrl + "\nScrivi nel ticket che NON sei Premium e vuoi verificarti come SP.";
            } else {
                // Premium: flusso normale verifica
                boolean alreadyVerified = apiClient.isPlayerVerified(player.getName());
                if (alreadyVerified) {
                    kickMsg = buildAlreadyVerifiedMessage(url);
                    msgPlain = "Questo account è già verificato.\nVisita: " + url + "\nPer scollegare l'account, segui le istruzioni sul sito.";
                } else {
                    // Nuovo controllo: troppi codici non usati
                    int threshold = plugin.getConfig().getInt("max_unconsumed_codes_per_player", 5);
                    int unusedCount = apiClient.countUnusedCodes(player.getName());
                    plugin.getLogger().info("[VerificaBlocksy] unused_codes=" + unusedCount + " threshold=" + threshold + " for " + player.getName());

                    if (unusedCount >= threshold) {
                        // Non generare nuovo codice: kick con messaggio specifico
                        kickMsg = buildTooManyCodesMessage();
                        msgPlain = "Ci sono già troppi codici generati per questo account, riprova tra 5 minuti.";
                    } else {
                        // Genera il codice (lunghezza da config)
                        int len = plugin.getConfig().getInt("code_length", 6);
                        final String code = generateNumericCode(len);

                        // Registra il codice
                        boolean ok = apiClient.registerVerificationCode(player.getName(), code);

                        kickMsg = buildVerificationMessage(url, code);
                        msgPlain = "Collega il tuo account sul sito:\n" + url + "\nCodice: " + code + "\nValido 5 minuti.";
                    }
                }
            }

            boolean kickViaProxy = plugin.getConfig().getBoolean("kick_via_proxy", false);
            plugin.getLogger().info("[VerificaBlocksy] kick_via_proxy=" + kickViaProxy + " for " + player.getName());

            if (kickViaProxy) {
                // Invia motivo al proxy via Plugin Messaging e fallback se necessario
                Bukkit.getScheduler().runTask(plugin, () -> {
                    sendKickViaProxy(player, kickMsg);
                    plugin.getLogger().info("[VerificaBlocksy] Sent proxy disconnect for " + player.getName());
                });
                // Fallback dopo breve tempo se il proxy non ha disconnesso
                Bukkit.getScheduler().runTaskLater(plugin, () -> {
                    if (player.isOnline()) {
                        plugin.getLogger().warning("[VerificaBlocksy] Proxy did not disconnect, applying backend kick for " + player.getName());
                        try {
                            player.kick(kickMsg);
                        } catch (Throwable t) {
                            player.kickPlayer(msgPlain);
                        }
                    }
                }, 20L);
            } else {
                // Kick sul main thread (leggera attesa per proxy)
                Bukkit.getScheduler().runTaskLater(plugin, () -> {
                    try {
                        player.kick(kickMsg);
                    } catch (Throwable t) {
                        player.kickPlayer(msgPlain);
                    }
                }, 2L);
            }
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

    private Component boldCode(String code) {
        return Component.text(code)
                .color(NamedTextColor.GOLD)
                .decorate(TextDecoration.BOLD);
    }

    private Component buildSpKickMessage(String ticketUrl) {
        return Component.text("Questo account non è Premium.", NamedTextColor.RED)
                .append(Component.newline())
                .append(Component.text("Acquista un account su ", NamedTextColor.WHITE))
                .append(link("https://www.minecraft.net"))
                .append(Component.newline())
                .append(Component.text("Oppure apri un ticket: ", NamedTextColor.WHITE))
                .append(link(ticketUrl))
                .append(Component.newline())
                .append(Component.text("Scrivi nel ticket che NON sei Premium e vuoi verificarti come SP.", NamedTextColor.GRAY));
    }

    private Component buildAlreadyVerifiedMessage(String url) {
        return Component.text("Questo account è già verificato.", NamedTextColor.GREEN)
                .append(Component.newline())
                .append(Component.text("Visita: ", NamedTextColor.WHITE))
                .append(link(url))
                .append(Component.newline())
                .append(Component.text("Per scollegare l'account, segui le istruzioni sul sito.", NamedTextColor.GRAY));
    }

    private Component buildVerificationMessage(String url, String code) {
        return Component.text("Collega il tuo account sul sito:", NamedTextColor.WHITE)
                .append(Component.newline())
                .append(link(url))
                .append(Component.newline())
                .append(Component.text("Codice: ", NamedTextColor.WHITE))
                .append(boldCode(code))
                .append(Component.newline())
                .append(Component.text("Valido 5 minuti.", NamedTextColor.GRAY));
    }

    private Component buildTooManyCodesMessage() {
        return Component.text("Ci sono già troppi codici generati per questo account.", NamedTextColor.RED)
                .append(Component.newline())
                .append(Component.text("Riprova tra 5 minuti.", NamedTextColor.GRAY));
    }

    private Component link(String url) {
        return Component.text(url, NamedTextColor.AQUA)
                .decorate(TextDecoration.UNDERLINED)
                .clickEvent(ClickEvent.openUrl(url))
                .hoverEvent(HoverEvent.showText(Component.text("Apri " + url, NamedTextColor.GRAY)));
    }

    private void sendKickViaProxy(Player player, Component kickMsg) {
        try {
            String json = GsonComponentSerializer.gson().serialize(kickMsg);
            ByteArrayOutputStream baos = new ByteArrayOutputStream();
            DataOutputStream dos = new DataOutputStream(baos);
            dos.writeUTF(json);
            dos.flush();
            player.sendPluginMessage(plugin, "verificablocksy:kick", baos.toByteArray());
        } catch (Exception e) {
            plugin.getLogger().warning("[VerificaBlocksy] Error sending proxy kick: " + e.getMessage());
            // Se qualcosa va storto, esegue fallback kick standard
            Bukkit.getScheduler().runTask(plugin, () -> {
                if (player.isOnline()) {
                    try {
                        player.kick(kickMsg);
                    } catch (Throwable t) {
                        player.kickPlayer("Disconnessione richiesta");
                    }
                }
            });
        }
    }
}