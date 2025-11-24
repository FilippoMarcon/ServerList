package me.ph1llyon.blocksy.api;

/**
 * Rappresenta un voto ricevuto dall'API Blocksy
 * Simile a McItaVote di MinecraftITALIA
 */
public class BlocksyVote {
    private long id;
    private long serverId;
    private String username;
    private String timestamp;
    
    public BlocksyVote() {}
    
    public BlocksyVote(long id, long serverId, String username, String timestamp) {
        this.id = id;
        this.serverId = serverId;
        this.username = username;
        this.timestamp = timestamp;
    }
    
    public long getId() {
        return id;
    }
    
    public void setId(long id) {
        this.id = id;
    }
    
    public long getServerId() {
        return serverId;
    }
    
    public void setServerId(long serverId) {
        this.serverId = serverId;
    }
    
    public String getUsername() {
        return username;
    }
    
    public void setUsername(String username) {
        this.username = username;
    }
    
    public String getTimestamp() {
        return timestamp;
    }
    
    public void setTimestamp(String timestamp) {
        this.timestamp = timestamp;
    }
    
    @Override
    public String toString() {
        return "BlocksyVote{id=" + id + ", serverId=" + serverId + 
               ", username='" + username + "', timestamp='" + timestamp + "'}";
    }
}
