package me.ph1llyon.blocksy.config;

import me.ph1llyon.blocksy.Blocksy;
import org.bukkit.configuration.file.FileConfiguration;

public class ConfigManager {
    
    private final Blocksy plugin;
    private String apiUrl;
    private String apiKey;
    private int timeoutSeconds;
    private int maxRetries;
    private boolean debugMode;
    
    public ConfigManager(Blocksy plugin) {
        this.plugin = plugin;
    }
    
    public void loadConfig() {
        FileConfiguration config = plugin.getConfig();
        
        // Carica le impostazioni API
        this.apiUrl = config.getString("api.url", "http://localhost/ServerList/");
        this.apiKey = config.getString("api.key", "");
        this.timeoutSeconds = config.getInt("api.timeout", 30);
        this.maxRetries = config.getInt("api.max-retries", 3);
        this.debugMode = config.getBoolean("debug", false);
        
        // Aggiungi slash finale se manca
        if (!this.apiUrl.endsWith("/")) {
            this.apiUrl += "/";
        }
    }
    
    public String getApiUrl() {
        return apiUrl;
    }
    
    public String getApiKey() {
        return apiKey;
    }
    
    public int getTimeoutSeconds() {
        return timeoutSeconds;
    }
    
    public int getMaxRetries() {
        return maxRetries;
    }
    
    public boolean isDebugMode() {
        return debugMode;
    }
    
    public String getValidateCodeEndpoint() {
        return apiUrl + "validate_vote_code.php";
    }
    
    public String getCheckPendingVotesEndpoint() {
        return apiUrl + "check_pending_votes.php";
    }
    
    public String getAutoDistributeRewardEndpoint() {
        return apiUrl + "auto_distribute_reward.php";
    }
    
    public String getCheckPlayerPendingVotesEndpoint() {
        return apiUrl + "check_player_pending_votes.php";
    }
    
    public String getServerLicense() {
        return plugin.getConfig().getString("server-license", "");
    }
    
    /**
     * Ottiene l'identificatore del server (solo licenza)
     * Restituisce la licenza o stringa vuota se non impostata
     */
    public String getServerIdentifier() {
        return getServerLicense();
    }
    
    /**
     * Verifica se la licenza è configurata correttamente
     */
    public boolean isLicenseConfigured() {
        String license = getServerLicense();
        return license != null && !license.trim().isEmpty() && license.length() == 24;
    }
    
    public String getMessage(String key) {
        return plugin.getConfig().getString("messages." + key, "");
    }
    
    public String getMessage(String key, String defaultValue) {
        return plugin.getConfig().getString("messages." + key, defaultValue);
    }
    
    /**
     * Ricarica la configurazione dal file config.yml
     */
    public void reloadConfig() {
        plugin.reloadConfig();
        loadConfig();
    }
    
    /**
     * Aggiorna l'intervallo di controllo delle ricompense automatiche
     */
    public void setAutoRewardInterval(int seconds) {
        plugin.getConfig().set("auto-reward.check-interval", seconds);
        plugin.saveConfig();
        loadConfig(); // Ricarica le impostazioni in memoria
    }
}