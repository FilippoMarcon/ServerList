`sl_users`: 
id
minecraft_nick
password_hash
data_registrazione
is_admin

`sl_reward_logs`:
id
vote_code_id
server_id
license_key
user_id
minecraft_nick
commands_executed
reward_status
error_message
executed_at

`sl_servers`:
id
nome
banner_url
descrizione
ip
versione
logo_url
tipo_server
owner_id
data_inserimento
data_aggiornamento
is_active
modalita {Modalità di gioco del server (JSON array)}

`sl_server_licenses`:
id
server_id
license_key
is_active
created_at
last_used
usage_count

`sl_sponsored_servers`:
id
server_id
priority
is_active
created_at
expires_at

`sl_votes`:
id
server_id
license_key
user_id
data_voto

`sl_vote_codes`:
id
vote_id
server_id
license_key
user_id
code
status
created_at
used_at
expires_at
player_name