package me.ph1llyon.blocksy;

import com.vexsoftware.votifier.model.Vote;
import com.vexsoftware.votifier.model.VotifierEvent;
import me.ph1llyon.blocksy.api.BlocksyAPI;
import me.ph1llyon.blocksy.api.BlocksyVote;
import org.bukkit.Bukkit;
import org.bukkit.scheduler.BukkitTask;

import java.util.List;

/**
 * Sistema di polling per recuperare voti dall'API
 * Simile a VoteChecker di MinecraftITALIA
 */
public class VoteChecker {
    
    private final Blocksy plugin;
    private final BlocksyAPI api;
    private final String apiKey;
    private final int checkInterval;
    private BukkitTask task;
    private boolean running;
    
    public VoteChecker(Blocksy plugin, String apiKey, int checkInterval) {
        this.plugin = plugin;
        this.api = new BlocksyAPI();
        this.apiKey = apiKey;
        this.checkInterval = checkInterval;
        this.running = false;
    }
    
    /**
     * Avvia il loop di controllo voti
     */
    public void start() {
        if (running) {
            return;
        }
        
        running = true;
        plugin.getLogger().info("Avvio sistema di polling voti...");
        plugin.getLogger().info("Controllo ogni " + checkInterval + " secondi");
        
        // Converti secondi in ticks (20 ticks = 1 secondo)
        long intervalTicks = checkInterval * 20L;
        
        // Avvia task asincrono ripetuto
        task = Bukkit.getScheduler().runTaskTimerAsynchronously(plugin, () -> {
            checkForVotes();
        }, 20L, intervalTicks); // Inizia dopo 1 secondo, poi ogni X secondi
    }
    
    /**
     * Ferma il loop di controllo voti
     */
    public void stop() {
        if (!running) {
            return;
        }
        
        running = false;
        if (task != null) {
            task.cancel();
            task = null;
        }
        plugin.getLogger().info("Sistema di polling voti fermato");
    }
    
    /**
     * Controlla se ci sono nuovi voti
     */
    private void checkForVotes() {
        try {
            // Recupera voti dall'API
            List<BlocksyVote> votes = api.fetchVotes(apiKey);
            
            if (votes.isEmpty()) {
                return; // Nessun voto pendente
            }
            
            plugin.getLogger().info("Trovati " + votes.size() + " voti pendenti");
            
            // Processa ogni voto
            for (BlocksyVote vote : votes) {
                processVote(vote);
            }
            
        } catch (Exception e) {
            plugin.getLogger().warning("Errore nel controllo voti: " + e.getMessage());
        }
    }
    
    /**
     * Processa un singolo voto
     */
    private void processVote(BlocksyVote vote) {
        try {
            plugin.getLogger().info("Processando voto: " + vote.getUsername() + " (ID: " + vote.getId() + ")");
            
            // Crea un oggetto Vote di NuVotifier
            Vote votifierVote = new Vote();
            votifierVote.setUsername(vote.getUsername());
            votifierVote.setServiceName("Blocksy");
            votifierVote.setAddress("blocksy.it");
            votifierVote.setTimeStamp(vote.getTimestamp());
            
            // Invia l'evento Votifier nel thread principale
            Bukkit.getScheduler().runTask(plugin, () -> {
                try {
                    VotifierEvent event = new VotifierEvent(votifierVote);
                    Bukkit.getPluginManager().callEvent(event);
                    plugin.getLogger().info("✓ Voto inviato a Votifier per " + vote.getUsername());
                } catch (Exception e) {
                    plugin.getLogger().severe("✗ Errore nell'invio evento Votifier: " + e.getMessage());
                }
            });
            
        } catch (Exception e) {
            plugin.getLogger().severe("Errore nel processare voto ID " + vote.getId() + ": " + e.getMessage());
        }
    }
    
    public boolean isRunning() {
        return running;
    }
}
