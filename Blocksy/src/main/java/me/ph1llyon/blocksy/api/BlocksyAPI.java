package me.ph1llyon.blocksy.api;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.lang.reflect.Type;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;

/**
 * Client API per recuperare voti dal sito Blocksy
 * Simile a McItaAPI di MinecraftITALIA
 */
public class BlocksyAPI {
    
    private static final String API_URL = "https://www.blocksy.it/api/vote/fetch";
    private static final int TIMEOUT = 10000; // 10 secondi
    private final Gson gson;
    
    public BlocksyAPI() {
        this.gson = new Gson();
    }
    
    /**
     * Recupera i voti pendenti dall'API
     * @param apiKey La chiave API del server
     * @return Lista di voti pendenti
     */
    public List<BlocksyVote> fetchVotes(String apiKey) {
        HttpURLConnection connection = null;
        try {
            // Costruisci URL con parametro apiKey
            String urlString = API_URL + "?apiKey=" + apiKey;
            URL url = new URL(urlString);
            
            // Apri connessione
            connection = (HttpURLConnection) url.openConnection();
            connection.setRequestMethod("GET");
            connection.setConnectTimeout(TIMEOUT);
            connection.setReadTimeout(TIMEOUT);
            connection.setRequestProperty("User-Agent", "Blocksy-Plugin/1.0");
            connection.setRequestProperty("Accept", "application/json");
            
            // Controlla risposta
            int responseCode = connection.getResponseCode();
            if (responseCode != 200) {
                throw new Exception("HTTP " + responseCode + ": " + connection.getResponseMessage());
            }
            
            // Leggi risposta
            BufferedReader reader = new BufferedReader(
                new InputStreamReader(connection.getInputStream())
            );
            StringBuilder response = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                response.append(line);
            }
            reader.close();
            
            // Parse JSON
            Type listType = new TypeToken<List<BlocksyVote>>(){}.getType();
            List<BlocksyVote> votes = gson.fromJson(response.toString(), listType);
            
            return votes != null ? votes : new ArrayList<>();
            
        } catch (Exception e) {
            System.err.println("Errore nel recupero voti: " + e.getMessage());
            return new ArrayList<>();
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }
}
