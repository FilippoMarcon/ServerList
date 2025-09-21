package me.ph1llyon.blocksy.commands;

import me.ph1llyon.blocksy.Blocksy;
import org.bukkit.ChatColor;
import org.bukkit.command.Command;
import org.bukkit.command.CommandExecutor;
import org.bukkit.command.CommandSender;

public class BlocksyCommand implements CommandExecutor {
    
    private final Blocksy plugin;
    
    public BlocksyCommand(Blocksy plugin) {
        this.plugin = plugin;
    }
    
    @Override
    public boolean onCommand(CommandSender sender, Command command, String label, String[] args) {
        if (args.length == 0) {
            sendHelp(sender);
            return true;
        }
        
        String subCommand = args[0].toLowerCase();
        
        switch (subCommand) {
            case "debug":
                handleDebug(sender);
                break;
            case "reload":
                handleReload(sender);
                break;
            case "interval":
                handleInterval(sender, args);
                break;
            case "help":
                sendHelp(sender);
                break;
            default:
                sendHelp(sender);
                break;
        }
        
        return true;
    }
    
    private void sendHelp(CommandSender sender) {
        sender.sendMessage(ChatColor.GOLD + "=== " + ChatColor.YELLOW + "Blocksy Help" + ChatColor.GOLD + " ===");
        sender.sendMessage(ChatColor.GRAY + "Il sistema di votazione è completamente automatico!");
        sender.sendMessage("");
        sender.sendMessage(ChatColor.YELLOW + "Comandi disponibili:");
        sender.sendMessage(ChatColor.GOLD + "/blocksy help" + ChatColor.GRAY + " - Mostra questo messaggio di aiuto");
        
        if (sender.hasPermission("blocksy.debug") || sender.hasPermission("blocksy.admin")) {
            sender.sendMessage(ChatColor.GOLD + "/blocksy debug" + ChatColor.GRAY + " - Mostra informazioni di debug");
        }
        
        if (sender.hasPermission("blocksy.reload") || sender.hasPermission("blocksy.admin")) {
            sender.sendMessage(ChatColor.GOLD + "/blocksy reload" + ChatColor.GRAY + " - Ricarica la configurazione");
        }
        
        if (sender.hasPermission("blocksy.interval") || sender.hasPermission("blocksy.admin")) {
            sender.sendMessage(ChatColor.GOLD + "/blocksy interval <secondi>" + ChatColor.GRAY + " - Imposta l'intervallo di controllo (10-3600)");
        }
        
        sender.sendMessage("");
        sender.sendMessage(ChatColor.GRAY + "Il plugin controllerà automaticamente i voti pendenti");
        sender.sendMessage(ChatColor.GRAY + "e distribuirà le ricompense quando disponibili.");
    }
    
    private void handleDebug(CommandSender sender) {
        if (!sender.hasPermission("blocksy.debug")) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&', 
                "&cNon hai il permesso di usare questo comando!"));
            return;
        }
        
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&8&l========== &6&lBlocksy Debug &8&l=========="));
        
        // Info licenza
        String license = plugin.getConfigManager().getServerIdentifier();
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&eLicenza: &7" + (license != null && !license.isEmpty() ? "&aConfigurata" : "&cNon configurata")));
        
        // Info auto-reward
        boolean autoReward = plugin.getConfig().getBoolean("auto-reward.enabled", true);
        int interval = plugin.getConfig().getInt("auto-reward.check-interval", 60);
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&eAuto-Reward: " + (autoReward ? "&aAbilitato" : "&cDisabilitato")));
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&eIntervallo: &7" + interval + " secondi"));
        
        // Info endpoint
        String checkEndpoint = plugin.getConfigManager().getCheckPendingVotesEndpoint();
        String distributeEndpoint = plugin.getConfigManager().getAutoDistributeRewardEndpoint();
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&eEndpoint Check: &7" + (checkEndpoint != null ? "&aConfigurato" : "&cNon configurato")));
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&eEndpoint Distribute: &7" + (distributeEndpoint != null ? "&aConfigurato" : "&cNon configurato")));
        
        sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
            "&8&l=============================="));
    }
    
    private void handleReload(CommandSender sender) {
        if (!sender.hasPermission("blocksy.reload")) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&', 
                "&cNon hai il permesso di usare questo comando!"));
            return;
        }
        
        try {
            plugin.reloadConfig();
            plugin.getConfigManager().reloadConfig();
            
            // Ricarica il task delle ricompense automatiche
            plugin.getRewardManager().stopAutoRewardTask();
            plugin.getRewardManager().startAutoRewardTask();
            
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&aConfigurazione ricaricata con successo!"));
            
            plugin.getLogger().info("Configurazione ricaricata da " + sender.getName());
            
        } catch (Exception e) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&cErrore durante il ricaricamento: &7" + e.getMessage()));
            plugin.getLogger().warning("Errore durante ricaricamento config da " + sender.getName() + ": " + e.getMessage());
        }
    }
    
    private void handleInterval(CommandSender sender, String[] args) {
        if (!sender.hasPermission("blocksy.interval")) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&', 
                "&cNon hai il permesso di usare questo comando!"));
            return;
        }
        
        if (args.length < 2) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&cUso: /blocksy interval <numero>"));
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&7Numero deve essere compreso tra 10 e 3600 secondi"));
            return;
        }
        
        try {
            int newInterval = Integer.parseInt(args[1]);
            
            if (newInterval < 10 || newInterval > 3600) {
                sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                    "&cL'intervallo deve essere compreso tra 10 e 3600 secondi!"));
                return;
            }
            
            // Aggiorna la configurazione
            plugin.getConfigManager().setAutoRewardInterval(newInterval);
            
            // Ricarica e riavvia il task
            plugin.getRewardManager().stopAutoRewardTask();
            plugin.getRewardManager().startAutoRewardTask();
            
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&aIntervallo impostato a &e" + newInterval + " secondi&a!"));
            
            plugin.getLogger().info("Intervallo ricompense automatiche cambiato a " + newInterval + " secondi da " + sender.getName());
            
        } catch (NumberFormatException e) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&cIl valore deve essere un numero valido!"));
        } catch (Exception e) {
            sender.sendMessage(ChatColor.translateAlternateColorCodes('&',
                "&cErrore durante l'impostazione dell'intervallo: &7" + e.getMessage()));
            plugin.getLogger().warning("Errore durante cambio intervallo da " + sender.getName() + ": " + e.getMessage());
        }
    }
}