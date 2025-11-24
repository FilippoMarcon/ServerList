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
        // Sistema di polling voti - non serve controllare al login
        // I voti vengono processati automaticamente ogni X secondi
        
        // Opzionale: invia messaggio di benvenuto
        if (plugin.getConfig().getBoolean("show-vote-reminder-on-join", false)) {
            plugin.getServer().getScheduler().runTaskLater(plugin, () -> {
                event.getPlayer().sendMessage("§7Vota per il server su §bhttps://www.blocksy.it");
            }, 60L);
        }
    }
}