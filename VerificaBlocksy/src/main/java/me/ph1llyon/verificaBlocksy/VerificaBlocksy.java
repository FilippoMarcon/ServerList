package me.ph1llyon.verificaBlocksy;

import me.ph1llyon.verificaBlocksy.http.WebApiClient;
import org.bukkit.plugin.java.JavaPlugin;

import java.util.logging.Logger;

public class VerificaBlocksy extends JavaPlugin {
    private WebApiClient apiClient;

    @Override
    public void onEnable() {
        saveDefaultConfig();

        String baseUrl = getConfig().getString("api_base_url", "https://www.blocksy.it");
        String registerPath = getConfig().getString("registration_endpoint_path", "/register_verification_code.php");
        String checkPath = getConfig().getString("check_verification_endpoint_path", "/check_minecraft_link.php");
        String countPath = getConfig().getString("count_unconsumed_endpoint_path", "/count_unused_verification_codes.php");
        boolean mojangStrict = getConfig().getBoolean("mojang_check_strict", false);

        Logger logger = getLogger();
        this.apiClient = new WebApiClient(baseUrl, registerPath, checkPath, countPath, mojangStrict, logger);

        getServer().getMessenger().registerOutgoingPluginChannel(this, "verificablocksy:kick");

        getServer().getPluginManager().registerEvents(new me.ph1llyon.verificaBlocksy.listener.PlayerJoinListener(this, apiClient), this);
    }

    public WebApiClient getApiClient() {
        return apiClient;
    }
}
