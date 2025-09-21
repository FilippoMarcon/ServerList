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
        
        getLogger().info("§aBlocksy Vote Plugin abilitato con successo!");
        getLogger().info("§aVersione: " + getDescription().getVersion());
        getLogger().info("§aAutore: " + getDescription().getAuthors().get(0));
    }

    @Override
    public void onDisable() {
        // Ferma il task di ricompense automatiche
        if (rewardManager != null) {
            rewardManager.stopAutoRewardTask();
        }
        getLogger().info("§cBlocksy Vote Plugin disabilitato!");
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
}
