package me.ph1llyon.blocksy.listeners;

import com.vexsoftware.votifier.model.Vote;
import com.vexsoftware.votifier.model.VotifierEvent;
import me.ph1llyon.blocksy.Blocksy;
import org.bukkit.Bukkit;
import org.bukkit.entity.Player;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;

import java.util.List;

public class VotifierListener implements Listener {
    
    private final Blocksy plugin;
    
    public VotifierListener(Blocksy plugin) {
        this.plugin = plugin;
    }
    
    @EventHandler
    public void onVotifierVote(VotifierEvent event) {
        Vote vote = event.getVote();
        String playerName = vote.getUsername();
        
        plugin.getLogger().info("§a[Votifier] Voto ricevuto per: " + playerName);
        
        // Trova il player online
        Player player = Bukkit.getPlayerExact(playerName);
        
        if (player != null && player.isOnline()) {
            // Player online - dai reward subito
            Bukkit.getScheduler().runTask(plugin, () -> {
                giveReward(player);
            });
        } else {
            // Player offline - salva per dopo
            plugin.getLogger().info("§e[Votifier] Player offline, reward salvato per il prossimo login");
            // Il sistema esistente di checkAndGiveReward lo gestirà al login
        }
    }
    
    private void giveReward(Player player) {
        // Controlla se le reward sono abilitate
        if (!plugin.getConfig().getBoolean("rewards.enabled", true)) {
            return;
        }
        
        // Esegui i comandi reward
        List<String> commands = plugin.getConfig().getStringList("rewards.commands");
        
        if (commands.isEmpty()) {
            plugin.getLogger().warning("Nessun comando reward configurato!");
            return;
        }
        
        for (String command : commands) {
            command = command.replace("{player}", player.getName());
            Bukkit.dispatchCommand(Bukkit.getConsoleSender(), command);
        }
        
        // Messaggio al player
        String message = plugin.getConfig().getString("rewards.message", 
            "§a§lGRAZIE PER AVER VOTATO!\n§7Hai ricevuto il tuo reward!");
        player.sendMessage(message.replace("&", "§"));
        
        plugin.getLogger().info("§a[Votifier] Reward dato a " + player.getName());
    }
}
