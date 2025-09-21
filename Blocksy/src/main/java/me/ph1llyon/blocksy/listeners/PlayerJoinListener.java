package me.ph1llyon.blocksy.listeners;

import me.ph1llyon.blocksy.Blocksy;
import org.bukkit.event.EventHandler;
import org.bukkit.event.Listener;
import org.bukkit.event.player.PlayerJoinEvent;

public class PlayerJoinListener implements Listener {
    
    private final Blocksy plugin;
    
    public PlayerJoinListener(Blocksy plugin) {
        this.plugin = plugin;
    }
    
    @EventHandler
    public void onPlayerJoin(PlayerJoinEvent event) {
        // Invia un messaggio di benvenuto con informazioni sul voto
        plugin.getServer().getScheduler().runTaskLater(plugin, () -> {
            if (plugin.getConfig().getBoolean("show-vote-reminder-on-join", true)) {
                plugin.getRewardManager().sendVoteReminder(event.getPlayer());
            }
        }, 60L); // Aspetta 3 secondi dopo il join
        
        // Avvia immediatamente il controllo per ricompense automatiche per questo giocatore
        if (plugin.getConfig().getBoolean("auto-reward.enabled", true)) {
            plugin.getServer().getScheduler().runTaskLater(plugin, () -> {
                plugin.getRewardManager().checkAutoRewardForPlayer(event.getPlayer());
            }, 100L); // Aspetta 5 secondi dopo il join
        }
    }
}