package me.ph1llyon.verificaBlocksy.proxy;

import com.google.inject.Inject;
import com.velocitypowered.api.event.Subscribe;
import com.velocitypowered.api.event.plugin.PluginMessageEvent;
import com.velocitypowered.api.event.proxy.ProxyInitializeEvent;
import com.velocitypowered.api.plugin.Plugin;
import com.velocitypowered.api.proxy.ProxyServer;
import com.velocitypowered.api.proxy.ServerConnection;
import com.velocitypowered.api.proxy.player.Player;
import com.velocitypowered.api.util.identity.Identity;
import com.velocitypowered.api.util.MinecraftChannelIdentifier;
import net.kyori.adventure.text.Component;
import net.kyori.adventure.text.serializer.gson.GsonComponentSerializer;

import java.io.ByteArrayInputStream;
import java.io.DataInputStream;
import java.nio.charset.StandardCharsets;
import java.util.logging.Logger;

@Plugin(id = "verificablocksyproxy", name = "VerificaBlocksyProxy", version = "1.0", authors = {"Ph1llyOn_"})
public class VerificaBlocksyProxy {
    private final ProxyServer server;
    private final Logger logger;
    private final MinecraftChannelIdentifier channel = MinecraftChannelIdentifier.from("verificablocksy:kick");

    @Inject
    public VerificaBlocksyProxy(ProxyServer server) {
        this.server = server;
        this.logger = Logger.getLogger("VerificaBlocksyProxy");
    }

    @Subscribe
    public void onInit(ProxyInitializeEvent e) {
        server.getChannelRegistrar().register(channel);
        logger.info("[VerificaBlocksyProxy] Canale registrato: " + channel.getId());
    }

    @Subscribe
    public void onPluginMessage(PluginMessageEvent event) {
        if (!event.getIdentifier().equals(channel)) {
            return;
        }
        if (!(event.getSource() instanceof ServerConnection)) {
            return;
        }
        ServerConnection source = (ServerConnection) event.getSource();
        Player player = source.getPlayer();
        try {
            DataInputStream in = new DataInputStream(new ByteArrayInputStream(event.getData()));
            String json = in.readUTF();
            Component reason = GsonComponentSerializer.gson().deserialize(json);
            // Disconnette lato proxy per evitare l'intestazione "You were kicked from <server>"
            player.disconnect(reason);
        } catch (Exception ex) {
            logger.warning("[VerificaBlocksyProxy] Errore parsing messaggio: " + ex.getMessage());
            player.disconnect(Component.text("Disconnessione"));
        }
        // Ferma l'eventuale inoltro del messaggio
        event.setResult(PluginMessageEvent.ForwardResult.handled());
    }
}