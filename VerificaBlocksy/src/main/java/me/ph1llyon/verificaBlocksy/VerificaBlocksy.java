package me.ph1llyon.verificaBlocksy;

import me.ph1llyon.verificaBlocksy.http.WebApiClient;
import me.ph1llyon.verificaBlocksy.listener.PlayerJoinListener;
import org.bukkit.Bukkit;
import org.bukkit.plugin.java.JavaPlugin;

public final class VerificaBlocksy extends JavaPlugin {

    @Override
    public void onEnable() {
        // Carica/crea config
        saveDefaultConfig();

        String baseUrl = getConfig().getString("api_base_url", "http://localhost:8000");
        String endpoint = getConfig().getString("registration_endpoint_path", "/register_verification_code.php");
        String checkEndpoint = getConfig().getString("check_verification_endpoint_path", "/check_minecraft_link.php");
        boolean mojangStrict = getConfig().getBoolean("mojang_check_strict", false);

        WebApiClient client = new WebApiClient(baseUrl, endpoint, checkEndpoint, mojangStrict, getLogger());
        Bukkit.getPluginManager().registerEvents(new PlayerJoinListener(this, client), this);
    }

    @Override
    public void onDisable() {
        // Nessuna logica di shutdown necessaria al momento
    }
}
