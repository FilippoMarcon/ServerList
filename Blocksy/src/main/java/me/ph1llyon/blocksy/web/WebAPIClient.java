package me.ph1llyon.blocksy.web;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import me.ph1llyon.blocksy.Blocksy;
import me.ph1llyon.blocksy.config.ConfigManager;
import org.bukkit.Bukkit;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.concurrent.CompletableFuture;

public class WebAPIClient {
    
    private final Blocksy plugin;
    private final ConfigManager configManager;
    
    public WebAPIClient(Blocksy plugin) {
        this.plugin = plugin;
        this.configManager = plugin.getConfigManager();
    }
    
    public CompletableFuture<String> makeRawPostRequest(String endpoint, String jsonData) {
        return CompletableFuture.supplyAsync(() -> {
            return makePostRequest(endpoint, jsonData);
        });
    }
    
    public CompletableFuture<JsonObject> validateVoteCode(String code, int serverId, String playerName) {
        return CompletableFuture.supplyAsync(() -> {
            try {
                JsonObject requestData = new JsonObject();
                requestData.addProperty("code", code);
                requestData.addProperty("server_id", serverId);
                requestData.addProperty("player_name", playerName);
                requestData.addProperty("timestamp", System.currentTimeMillis());
                
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Validazione codice: " + code + " per server: " + serverId + " player: " + playerName);
                    plugin.getLogger().info("[Blocksy Debug] URL: " + configManager.getValidateCodeEndpoint());
                    plugin.getLogger().info("[Blocksy Debug] Dati richiesta: " + requestData.toString());
                }
                
                String response = makePostRequest(configManager.getValidateCodeEndpoint(), requestData.toString());
                
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Risposta ricevuta: " + response);
                }
                
                if (response != null) {
                    return JsonParser.parseString(response).getAsJsonObject();
                }
                
                JsonObject errorResponse = new JsonObject();
                errorResponse.addProperty("success", false);
                errorResponse.addProperty("error", "Nessuna risposta dal server");
                return errorResponse;
                
            } catch (Exception e) {
                plugin.getLogger().warning("Errore durante la validazione del codice: " + e.getMessage());
                if (configManager.isDebugMode()) {
                    e.printStackTrace();
                }
                
                JsonObject errorResponse = new JsonObject();
                errorResponse.addProperty("success", false);
                errorResponse.addProperty("error", "Errore di connessione al server");
                return errorResponse;
            }
        });
    }
    
    public CompletableFuture<JsonObject> validateVoteCodeWithLicense(String code, String licenseKey, String playerName) {
        return CompletableFuture.supplyAsync(() -> {
            try {
                JsonObject requestData = new JsonObject();
                requestData.addProperty("code", code);
                requestData.addProperty("license_key", licenseKey);
                requestData.addProperty("player_name", playerName);
                requestData.addProperty("timestamp", System.currentTimeMillis());
                
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Validazione codice: " + code + " con licenza: " + licenseKey + " player: " + playerName);
                    plugin.getLogger().info("[Blocksy Debug] URL: " + configManager.getValidateCodeEndpoint());
                    plugin.getLogger().info("[Blocksy Debug] Dati richiesta: " + requestData.toString());
                }
                
                String response = makePostRequest(configManager.getValidateCodeEndpoint(), requestData.toString());
                
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Risposta ricevuta: " + response);
                }
                
                if (response != null) {
                    return JsonParser.parseString(response).getAsJsonObject();
                }
                
                JsonObject errorResponse = new JsonObject();
                errorResponse.addProperty("success", false);
                errorResponse.addProperty("error", "Nessuna risposta dal server");
                return errorResponse;
                
            } catch (Exception e) {
                plugin.getLogger().warning("Errore durante la validazione del codice con licenza: " + e.getMessage());
                if (configManager.isDebugMode()) {
                    e.printStackTrace();
                }
                
                JsonObject errorResponse = new JsonObject();
                errorResponse.addProperty("success", false);
                errorResponse.addProperty("error", "Errore di connessione al server");
                return errorResponse;
            }
        });
    }
    
    private String makePostRequest(String endpoint, String jsonData) {
        HttpURLConnection connection = null;
        
        try {
            URL url = new URL(endpoint);
            connection = (HttpURLConnection) url.openConnection();
            
            connection.setRequestMethod("POST");
            connection.setRequestProperty("Content-Type", "application/json");
            connection.setRequestProperty("Accept", "application/json");
            connection.setRequestProperty("User-Agent", "Blocksy-Minecraft-Plugin/1.0");
            
            if (configManager.getApiKey() != null && !configManager.getApiKey().isEmpty()) {
                connection.setRequestProperty("X-API-Key", configManager.getApiKey());
            }
            
            connection.setConnectTimeout(configManager.getTimeoutSeconds() * 1000);
            connection.setReadTimeout(configManager.getTimeoutSeconds() * 1000);
            connection.setDoOutput(true);
            
            if (configManager.isDebugMode()) {
                plugin.getLogger().info("[Blocksy Debug] Invio richiesta POST a: " + endpoint);
                plugin.getLogger().info("[Blocksy Debug] Headers: Content-Type=application/json, Accept=application/json, User-Agent=Blocksy-Minecraft-Plugin/1.0");
                plugin.getLogger().info("[Blocksy Debug] Dati: " + jsonData);
            }
            
            // Invia i dati
            try (OutputStream os = connection.getOutputStream()) {
                byte[] input = jsonData.getBytes(StandardCharsets.UTF_8);
                os.write(input, 0, input.length);
                
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Dati inviati: " + input.length + " bytes");
                }
            }
            
            int responseCode = connection.getResponseCode();
            
            if (configManager.isDebugMode()) {
                plugin.getLogger().info("[Blocksy Debug] Response code: " + responseCode);
            }
            
            if (responseCode == HttpURLConnection.HTTP_OK) {
                String response = new String(connection.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
                if (configManager.isDebugMode()) {
                    plugin.getLogger().info("[Blocksy Debug] Risposta ricevuta: " + response);
                }
                return response;
            } else {
                plugin.getLogger().warning("Risposta HTTP non valida: " + responseCode);
                if (configManager.isDebugMode()) {
                    String errorResponse = new String(connection.getErrorStream().readAllBytes(), StandardCharsets.UTF_8);
                    plugin.getLogger().info("[Blocksy Debug] Error response: " + errorResponse);
                }
                return null;
            }
            
        } catch (Exception e) {
            plugin.getLogger().warning("Errore durante la richiesta HTTP: " + e.getMessage());
            if (configManager.isDebugMode()) {
                e.printStackTrace();
            }
            return null;
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }
}