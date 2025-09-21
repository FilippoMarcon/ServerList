package me.ph1llyon.blocksy.rewards;

import com.google.gson.JsonArray;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import me.ph1llyon.blocksy.Blocksy;
import org.bukkit.Bukkit;
import org.bukkit.ChatColor;
import org.bukkit.entity.Player;
import org.bukkit.scheduler.BukkitRunnable;

import java.util.List;
import java.util.UUID;
import java.util.concurrent.CompletableFuture;
import java.util.concurrent.ConcurrentHashMap;

public class RewardManager {
    
    private final Blocksy plugin;
    private final ConcurrentHashMap<UUID, Long> lastVoteReminder = new ConcurrentHashMap<>();
    private BukkitRunnable autoRewardTask;
    
    public RewardManager(Blocksy plugin) {
        this.plugin = plugin;
        startAutoRewardTask();
    }
    
    public CompletableFuture<Boolean> processVoteReward(String code, Player player) {
        // Usa solo la licenza per identificare il server
        String serverLicense = plugin.getConfigManager().getServerIdentifier();
        
        if (!plugin.getConfigManager().isLicenseConfigured()) {
            player.sendMessage("§c§l[Blocksy] §cLicenza server non configurata! Contatta un amministratore.");
            plugin.getLogger().severe("Licenza server non configurata! Imposta 'server-license' nel config.yml");
            return CompletableFuture.completedFuture(false);
        }
        
        return plugin.getWebAPIClient().validateVoteCodeWithLicense(code, serverLicense, player.getName())
            .thenApply(response -> {
                if (!response.get("success").getAsBoolean()) {
                    String error = response.has("error") ? response.get("error").getAsString() : "Errore sconosciuto";
                    player.sendMessage("§c§l[Blocksy] §c" + error);
                    return false;
                }
                
                return executeRewardCommands(response, player);
            })
            .exceptionally(throwable -> {
                plugin.getLogger().severe("Errore durante l'elaborazione della ricompensa: " + throwable.getMessage());
                player.sendMessage("§c§l[Blocksy] §cSi è verificato un errore durante l'elaborazione della ricompensa.");
                return false;
            });
    }
    
    private boolean executeRewardCommands(JsonObject response, Player player) {
        try {
            String rewardName = response.has("reward_name") ? response.get("reward_name").getAsString() : "Ricompensa Voto";
            JsonArray commands = response.getAsJsonArray("commands");
            
            if (commands == null || commands.size() == 0) {
                player.sendMessage("§e§l[Blocksy] §eNessun comando da eseguire per questa ricompensa.");
                return true;
            }
            
            // Esegui i comandi con un delay per evitare lag
            new BukkitRunnable() {
                int commandIndex = 0;
                
                @Override
                public void run() {
                    if (commandIndex >= commands.size()) {
                        // Tutti i comandi eseguiti
                        player.sendMessage("§a§l[Blocksy] §a" + rewardName + " consegnata con successo!");
                        player.sendMessage("§7§oGrazie per aver votato per il nostro server!");
                        this.cancel();
                        return;
                    }
                    
                    String command = commands.get(commandIndex).getAsString();
                    commandIndex++;
                    
                    try {
                        // Sostituisci placeholder aggiuntivi
                        command = command.replace("{player}", player.getName());
                        command = command.replace("{world}", player.getWorld().getName());
                        
                        // Esegui il comando come console
                        boolean success = Bukkit.dispatchCommand(Bukkit.getConsoleSender(), command);
                        
                        if (!success) {
                            plugin.getLogger().warning("Comando fallito: " + command);
                        }
                        
                    } catch (Exception e) {
                        plugin.getLogger().warning("Errore durante l'esecuzione del comando '" + command + "': " + e.getMessage());
                    }
                }
            }.runTaskTimer(plugin, 0L, 10L); // Esegui un comando ogni 10 tick (0.5 secondi)
            
            return true;
            
        } catch (Exception e) {
            plugin.getLogger().severe("Errore durante l'esecuzione dei comandi di ricompensa: " + e.getMessage());
            player.sendMessage("§c§l[Blocksy] §cErrore durante l'elaborazione della ricompensa.");
            return false;
        }
    }
    
    public void sendVoteReminder(Player player) {
        UUID playerId = player.getUniqueId();
        long currentTime = System.currentTimeMillis();
        
        // Controlla se è passato abbastanza tempo dall'ultimo messaggio (5 minuti)
        if (lastVoteReminder.containsKey(playerId)) {
            long lastTime = lastVoteReminder.get(playerId);
            if (currentTime - lastTime < 300000) { // 5 minuti in millisecondi
                return;
            }
        }
        
        // Aggiorna il timestamp dell'ultimo messaggio
        lastVoteReminder.put(playerId, currentTime);
        
        // Controlla se ci sono voti pendenti per questo giocatore
        try {
            JsonObject requestData = new JsonObject();
            requestData.addProperty("player_name", player.getName());
            
            // Usa solo la licenza per identificare il server
            if (!plugin.getConfigManager().isLicenseConfigured()) {
                plugin.getLogger().warning("Licenza server non configurata! Impossibile controllare voti pendenti.");
                return;
            }
            requestData.addProperty("license_key", plugin.getConfigManager().getServerIdentifier());
            
            plugin.getWebAPIClient().makeRawPostRequest(
                plugin.getConfigManager().getCheckPlayerPendingVotesEndpoint(),
                requestData.toString()
            ).thenAccept(response -> {
                if (response != null) {
                    try {
                        JsonObject jsonResponse = JsonParser.parseString(response).getAsJsonObject();
                        
                        if (jsonResponse.get("success").getAsBoolean() && 
                            jsonResponse.get("has_pending_votes").getAsBoolean()) {
                            
                            // Solo se ci sono voti pendenti, mostra il messaggio
                            String message = plugin.getConfigManager().getMessage("vote-reminder");
                            if (message != null && !message.isEmpty()) {
                                player.sendMessage(ChatColor.translateAlternateColorCodes('&', 
                                    message.replace("%player%", player.getName())));
                            }
                        }
                    } catch (Exception e) {
                        plugin.getLogger().warning("Errore nel parsing della risposta per " + player.getName() + ": " + e.getMessage());
                    }
                }
            }).exceptionally(e -> {
                plugin.getLogger().warning("Errore nel controllo voti pendenti per " + player.getName() + ": " + e.getMessage());
                return null;
            });
            
        } catch (Exception e) {
            plugin.getLogger().warning("Errore nel controllo voti pendenti per " + player.getName() + ": " + e.getMessage());
        }
    }
    
    private CompletableFuture<Boolean> checkPendingVotes(Player player) {
        return CompletableFuture.supplyAsync(() -> {
            try {
                // Crea una richiesta per verificare se ci sono voti pendenti per questo giocatore
                JsonObject requestData = new JsonObject();
                requestData.addProperty("player_name", player.getName());
                requestData.addProperty("check_pending", true);
                
                // Usa solo la licenza per identificare il server
                if (!plugin.getConfigManager().isLicenseConfigured()) {
                    plugin.getLogger().warning("Licenza server non configurata! Impossibile controllare voti pendenti.");
                    return false;
                }
                requestData.addProperty("license_key", plugin.getConfigManager().getServerIdentifier());
                
                String response = plugin.getWebAPIClient().makeRawPostRequest(
                    plugin.getConfigManager().getCheckPendingVotesEndpoint(), 
                    requestData.toString()
                ).get(); // Aspetta la risposta
                
                if (response != null) {
                    JsonObject jsonResponse = JsonParser.parseString(response).getAsJsonObject();
                    return jsonResponse.get("has_pending").getAsBoolean();
                }
                
                return false;
            } catch (Exception e) {
                plugin.getLogger().warning("Errore durante il controllo voti pendenti: " + e.getMessage());
                return false;
            }
        });
    }
    
    public void startAutoRewardTask() {
        // Controlla se le ricompense automatiche sono abilitate
        if (!plugin.getConfig().getBoolean("auto-reward.enabled", true)) {
            plugin.getLogger().info("Ricompense automatiche disabilitate nella configurazione");
            return;
        }
        
        int interval = plugin.getConfig().getInt("auto-reward.check-interval", 60) * 20; // Converti secondi in ticks
        
        // Avvia il task di ricompense automatiche
        autoRewardTask = new BukkitRunnable() {
            @Override
            public void run() {
                checkAndDistributeAutoRewards();
            }
        };
        
        // Esegui con l'intervallo configurato
        autoRewardTask.runTaskTimer(plugin, interval, interval);
        plugin.getLogger().info("Ricompense automatiche avviate con intervallo di " + (interval/20) + " secondi");
    }
    
    private void checkAndDistributeAutoRewards() {
        // Controlla se le ricompense automatiche sono abilitate
        if (!plugin.getConfig().getBoolean("auto-reward.enabled", true)) {
            return;
        }
        
        // Controlla tutti i giocatori online
        for (Player player : Bukkit.getOnlinePlayers()) {
            distributeAutoReward(player);
        }
    }
    
    private void distributeAutoReward(Player player) {
        // Controlla se le ricompense automatiche sono abilitate
        if (!plugin.getConfig().getBoolean("auto-reward.enabled", true)) {
            return;
        }
        
        // Controlla se deve dare ricompense solo ai giocatori online
        if (plugin.getConfig().getBoolean("auto-reward.only-online", true) && !player.isOnline()) {
            return;
        }
        
        try {
            JsonObject requestData = new JsonObject();
            requestData.addProperty("player_name", player.getName());
            
            // Usa solo la licenza per identificare il server
            if (!plugin.getConfigManager().isLicenseConfigured()) {
                plugin.getLogger().warning("Licenza server non configurata! Impossibile distribuire ricompense automatiche.");
                return;
            }
            requestData.addProperty("license_key", plugin.getConfigManager().getServerIdentifier());
            
            plugin.getWebAPIClient().makeRawPostRequest(
                plugin.getConfigManager().getAutoDistributeRewardEndpoint(),
                requestData.toString()
            ).thenAccept(response -> {
                if (response != null) {
                    try {
                        JsonObject jsonResponse = JsonParser.parseString(response).getAsJsonObject();
                        
                        if (jsonResponse.get("success").getAsBoolean()) {
                            JsonArray rewards = jsonResponse.getAsJsonArray("rewards");
                            
                            for (int i = 0; i < rewards.size(); i++) {
                                JsonObject reward = rewards.get(i).getAsJsonObject();
                                String rewardCode = reward.get("code").getAsString();
                                
                                // Esegui i comandi di ricompensa
                                executeAutoRewardCommands(player, rewardCode);
                                
                                plugin.getLogger().info("Ricompensa automatica distribuita a " + player.getName() + " per il codice: " + rewardCode);
                            }
                        }
                    } catch (Exception e) {
                        plugin.getLogger().warning("Errore nel parsing della risposta per " + player.getName() + ": " + e.getMessage());
                    }
                }
            }).exceptionally(e -> {
                plugin.getLogger().warning("Errore nella distribuzione ricompensa automatica per " + player.getName() + ": " + e.getMessage());
                return null;
            });
            
        } catch (Exception e) {
            plugin.getLogger().warning("Errore nella distribuzione ricompensa automatica per " + player.getName() + ": " + e.getMessage());
        }
    }
    
    private void executeAutoRewardCommands(Player player, String rewardCode) {
        List<String> commands = plugin.getConfig().getStringList("auto-reward-commands");
        
        if (commands.isEmpty()) {
            plugin.getLogger().warning("Nessun comando configurato per le ricompense automatiche!");
            return;
        }
        
        // Esegui i comandi dal config.yml
        new BukkitRunnable() {
            int commandIndex = 0;
            
            @Override
            public void run() {
                if (commandIndex >= commands.size()) {
                    // Tutti i comandi eseguiti
                    player.sendMessage("§a§l[Blocksy] §aRicompensa automatica ricevuta!");
                    player.sendMessage("§7§oGrazie per aver votato per il nostro server!");
                    this.cancel();
                    return;
                }
                
                String command = commands.get(commandIndex);
                commandIndex++;
                
                try {
                    // Sostituisci i placeholder
                    command = command.replace("{player}", player.getName());
                    command = command.replace("{world}", player.getWorld().getName());
                    command = command.replace("{code}", rewardCode);
                    
                    // Esegui il comando come console
                    boolean success = Bukkit.dispatchCommand(Bukkit.getConsoleSender(), command);
                    
                    if (!success) {
                        plugin.getLogger().warning("Comando automatico fallito: " + command);
                    }
                    
                } catch (Exception e) {
                    plugin.getLogger().warning("Errore durante l'esecuzione del comando automatico '" + command + "': " + e.getMessage());
                }
            }
        }.runTaskTimer(plugin, 0L, 10L); // Esegui un comando ogni 10 tick (0.5 secondi)
    }
    
    public void checkAutoRewardForPlayer(Player player) {
        // Controlla subito se ci sono ricompense automatiche per questo giocatore
        distributeAutoReward(player);
    }
    
    public void stopAutoRewardTask() {
        if (autoRewardTask != null) {
            autoRewardTask.cancel();
        }
    }
}