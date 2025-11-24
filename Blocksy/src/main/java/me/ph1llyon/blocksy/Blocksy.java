package me.ph1llyon.blocksy;

import me.ph1llyon.blocksy.commands.BlocksyCommand;
import me.ph1llyon.blocksy.config.ConfigManager;
import me.ph1llyon.blocksy.listeners.PlayerJoinListener;
import me.ph1llyon.blocksy.rewards.RewardManager;
import me.ph1llyon.blocksy.web.WebAPIClient;
import org.bukkit.plugin.java.JavaPlugin;

public final class Blocksy extends JavaPlugin {
    
    private static Blocksy instance;
    private ConfigManager configManager;
    private RewardManager rewardManager;
    private WebAPIClient webAPIClient;
    private VoteChecker voteChecker;
    
    @Override
    public void onEnable() {
        instance = this;
        
        // Inizializza i manager
        configManager = new ConfigManager(this);
        rewardManager = new RewardManager(this);
        webAPIClient = new WebAPIClient(this);
        
        // Salva la configurazione di default
        saveDefaultConfig();
        configManager.loadConfig();
        
        // Registra i comandi
        getCommand("blocksy").setExecutor(new BlocksyCommand(this));
        
        // Registra gli eventilistener
        getServer().getPluginManager().registerEvents(new PlayerJoinListener(this), this);
        
        // Registra Votifier listener se Votifier è presente
        if (getServer().getPluginManager().getPlugin("Votifier") != null || 
            getServer().getPluginManager().getPlugin("NuVotifier") != null) {
            try {
                getServer().getPluginManager().registerEvents(new me.ph1llyon.blocksy.listeners.VotifierListener(this), this);
                getLogger().info("§aVotifier integrazione abilitata!");
            } catch (Exception e) {
                getLogger().warning("§cErrore nell'abilitare Votifier integrazione: " + e.getMessage());
            }
        } else {
            getLogger().info("§eVotifier non trovato - integrazione disabilitata");
        }
        
        // Avvia sistema di polling voti (come MinecraftITALIA)
        startVotePolling();
        
        getLogger().info("§aBlocksy Vote Plugin abilitato con successo!");
        getLogger().info("§aVersione: " + getDescription().getVersion());
        getLogger().info("§aAutore: " + getDescription().getAuthors().get(0));
    }

    @Override
    public void onDisable() {
        // Ferma il vote checker
        if (voteChecker != null) {
            voteChecker.stop();
        }
        
        // Ferma il task di ricompense automatiche
        if (rewardManager != null) {
            rewardManager.stopAutoRewardTask();
        }
        getLogger().info("§cBlocksy Vote Plugin disabilitato!");
    }
    
    /**
     * Avvia il sistema di polling voti dall'API
     * Sistema identico a MinecraftITALIA
     */
    private void startVotePolling() {
        String apiKey = getConfig().getString("api-key", "");
        int checkInterval = getConfig().getInt("check-interval", 5);
        
        if (apiKey.isEmpty()) {
            getLogger().warning("§c===========================================");
            getLogger().warning("§cAPI KEY NON CONFIGURATA!");
            getLogger().warning("§cConfigura 'api-key' in config.yml");
            getLogger().warning("§cIl sistema di voti non funzionerà!");
            getLogger().warning("§c===========================================");
            return;
        }
        
        getLogger().info("§eAvvio sistema di polling voti...");
        getLogger().info("§eAPI Key: " + apiKey.substring(0, Math.min(8, apiKey.length())) + "...");
        getLogger().info("§eIntervallo controllo: " + checkInterval + " secondi");
        
        voteChecker = new VoteChecker(this, apiKey, checkInterval);
        voteChecker.start();
    }
    
    public static Blocksy getInstance() {
        return instance;
    }
    
    public ConfigManager getConfigManager() {
        return configManager;
    }
    
    public RewardManager getRewardManager() {
        return rewardManager;
    }
    
    public WebAPIClient getWebAPIClient() {
        return webAPIClient;
    }
    
    public VoteChecker getVoteChecker() {
        return voteChecker;
    }
}
