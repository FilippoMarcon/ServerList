package me.ph1llyon.verificaBlocksy.http;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.logging.Logger;

public class WebApiClient {
    private final String baseUrl;
    private final String endpointPath;
    private final String checkPath;
    private final boolean mojangStrict;
    private final Logger logger;

    public WebApiClient(String baseUrl, String endpointPath, String checkEndpointPath, boolean mojangStrict, Logger logger) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl.substring(0, baseUrl.length() - 1) : baseUrl;
        this.endpointPath = endpointPath.startsWith("/") ? endpointPath : ("/" + endpointPath);
        this.checkPath = checkEndpointPath.startsWith("/") ? checkEndpointPath : ("/" + checkEndpointPath);
        this.mojangStrict = mojangStrict;
        this.logger = logger;
    }

    public boolean registerVerificationCode(String playerName, String code) {
        try {
            URL url = new URL(baseUrl + endpointPath);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setConnectTimeout(3000);
            conn.setReadTimeout(4000);
            conn.setDoOutput(true);
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            String body = "player_name=" + URLEncoder.encode(playerName, "UTF-8")
                    + "&code=" + URLEncoder.encode(code, "UTF-8");

            try (OutputStream os = conn.getOutputStream()) {
                os.write(body.getBytes(StandardCharsets.UTF_8));
            }

            int status = conn.getResponseCode();
            if (status >= 200 && status < 300) {
                return true;
            } else {
                logger.warning("VerificaBlocksy: HTTP " + status + " registering code for " + playerName);
                return false;
            }
        } catch (Exception e) {
            logger.warning("VerificaBlocksy: Error registering code: " + e.getMessage());
            return false;
        }
    }

    public boolean isPlayerVerified(String playerName) {
        try {
            URL url = new URL(baseUrl + checkPath);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setConnectTimeout(3000);
            conn.setReadTimeout(4000);
            conn.setDoOutput(true);
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            String body = "player_name=" + URLEncoder.encode(playerName, "UTF-8");
            try (OutputStream os = conn.getOutputStream()) {
                os.write(body.getBytes(StandardCharsets.UTF_8));
            }

            int status = conn.getResponseCode();
            if (status >= 200 && status < 300) {
                String response = new String(conn.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
                return response.contains("\"verified\":true");
            } else {
                logger.warning("VerificaBlocksy: HTTP " + status + " checking verification for " + playerName);
                return false;
            }
        } catch (Exception e) {
            logger.warning("VerificaBlocksy: Error checking verification: " + e.getMessage());
            return false;
        }
    }

    /**
     * Controlla se il nickname corrisponde ad un account Premium contattando l'API Mojang.
     * In caso di errore di rete o risposta inaspettata, assume Premium per evitare falsi positivi di kick.
     */
    public boolean isPremium(String playerName) {
        try {
            URL url = new URL("https://api.mojang.com/users/profiles/minecraft/" + URLEncoder.encode(playerName, "UTF-8"));
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            conn.setConnectTimeout(2500);
            conn.setReadTimeout(3000);

            int status = conn.getResponseCode();
            if (status == 200) {
                String response = new String(conn.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
                return response.contains("\"id\"");
            }
            if (status == 204 || status == 404) {
                return false; // non premium
            }
            logger.info("VerificaBlocksy: Mojang status " + status + " for " + playerName + ". Fallback " + (mojangStrict ? "strict -> SP" : "lenient -> Premium") + ".");
            return !mojangStrict;
        } catch (Exception e) {
            logger.info("VerificaBlocksy: Mojang check error: " + e.getMessage() + ". Fallback " + (mojangStrict ? "strict -> SP" : "lenient -> Premium") + ".");
            return !mojangStrict;
        }
    }
}