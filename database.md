`sl_users`: 
id
minecraft_nick
password_hash
data_registrazione
is_admin
email

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
(guarda profile.php per maggiori informazioni su per esempio is_active=2)
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
staff_list
website_url
shop_url
discord_url
telegram_url
social_links

`sl_server_licenses`:
(prendi spunto dal file generate server license)
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

`sl_annunci`:
id
title
body
author_id
is_published
created_at
updated_at

`sl_annunci_likes`:
id
annuncio_id
user_id
created_at

`sl_forum_categories`:
id
name
slug
sort_order

`sl_forum_threads`:
id
category_id
author_id
title
body
slug
replies_count
views
is_locked
created_at
updated_at

`sl_forum_posts`:
id
thread_id
author_id
body
created_at
updated_at

`sl_forum_subscriptions`:
id
thread_id
user_id
created_at

`sl_password_resets`:
id
user_id
token
expires_at
created_at