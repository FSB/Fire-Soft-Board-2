CREATE TABLE fsb2_auths (
  auth_name varchar(30) default '',
  auth_level tinyint(4) default '0',
  auth_begin tinyint(4) default '0'
);
CREATE INDEX fsb2_auths_auth_name_index ON fsb2_auths (auth_name);
CREATE TABLE fsb2_ban (
  ban_id INTEGER PRIMARY KEY NOT null,
  ban_type varchar(255) default '',
  ban_content varchar(255) default '',
  ban_length int(11) default '0',
  ban_reason varchar(255) default '',
  ban_cookie tinyint(4)
);
CREATE INDEX fsb2_ban_ban_length_index ON fsb2_ban (ban_length);
CREATE TABLE fsb2_bots (
  bot_id mediumint(9) auto_increment,
  bot_name varchar(255),
  bot_ip varchar(255),
  bot_agent varchar(255),
  bot_last int(11)
);
CREATE INDEX fsb2_bots_bot_id_index ON fsb2_bots (bot_id);
CREATE TABLE fsb2_cache (
  cache_hash varchar(255),
  cache_type varchar(255),
  cache_content longtext,
  cache_time int(11)
);
CREATE INDEX fsb2_cache_cache_hash_index ON fsb2_cache (cache_hash);
CREATE INDEX fsb2_cache_cache_type_index ON fsb2_cache (cache_type);
CREATE TABLE fsb2_calendar (
  c_id INTEGER PRIMARY KEY NOT null,
  c_begin int(11),
  c_end int(11),
  u_id int(11),
  c_title varchar(255),
  c_content text,
  c_approve tinyint(4),
  c_view tinyint(4)
);
CREATE INDEX fsb2_calendar_c_begin_index ON fsb2_calendar (c_begin);
CREATE INDEX fsb2_calendar_c_end_index ON fsb2_calendar (c_end);
CREATE TABLE fsb2_censor (
  censor_id mediumint(9) auto_increment,
  censor_word varchar(255) default '',
  censor_replace varchar(255) default '',
  censor_regexp tinyint(4) default '0'
);
CREATE INDEX fsb2_censor_censor_id_index ON fsb2_censor (censor_id);
CREATE TABLE fsb2_config (
  cfg_name varchar(255) default '',
  cfg_value varchar(255) default ''
);
CREATE INDEX fsb2_config_cfg_name_index ON fsb2_config (cfg_name);
CREATE TABLE fsb2_config_handler (
  cfg_cat varchar(30) default '',
  cfg_subcat varchar(30),
  cfg_name varchar(255) default '',
  cfg_function varchar(255) default '',
  cfg_args text,
  cfg_type varchar(255) default ''
);
CREATE INDEX fsb2_config_handler_cfg_cat_index ON fsb2_config_handler (cfg_cat);
CREATE INDEX fsb2_config_handler_cfg_subcat_index ON fsb2_config_handler (cfg_subcat);
CREATE TABLE fsb2_forums (
  f_id mediumint(9) auto_increment,
  f_left mediumint(9),
  f_right mediumint(9),
  f_rules text,
  f_cat_id mediumint(9) default '0',
  f_name varchar(255) default '',
  f_text text,
  f_parent mediumint(9) default '0',
  f_status tinyint(4) default '0',
  f_total_topic int(11) default '0',
  f_total_post int(11) default '0',
  f_last_p_id int(11) default '0',
  f_last_t_id int(11) default '0',
  f_last_p_time int(11) default '0',
  f_last_u_id int(11) default '0',
  f_last_p_nickname varchar(255),
  f_last_t_title varchar(255),
  f_level tinyint(4) default '0',
  f_prune_time int(11) default '0',
  f_prune_topic_type varchar(255) default '',
  f_type tinyint(4) default '0',
  f_map_default varchar(255) default '',
  f_map_first_post tinyint(4) default '0',
  f_location varchar(255) default '',
  f_location_view int(11) default '0',
  f_password varchar(255) default '',
  f_tpl varchar(255) default '',
  f_global_announce tinyint(4) default '0',
  f_approve tinyint(4) default '0',
  f_color varchar(255) default '',
  f_display_moderators tinyint(4) default '1',
  f_display_subforums tinyint(4) default '1'
);
CREATE INDEX fsb2_forums_f_id_index ON fsb2_forums (f_id);
CREATE INDEX fsb2_forums_f_left_index ON fsb2_forums (f_left);
CREATE INDEX fsb2_forums_f_right_index ON fsb2_forums (f_right);
CREATE TABLE fsb2_fsbcode (
  fsbcode_id smallint(5) auto_increment,
  fsbcode_tag varchar(20),
  fsbcode_search text,
  fsbcode_replace text,
  fsbcode_fct varchar(50),
  fsbcode_priority int(11),
  fsbcode_wysiwyg tinyint(4),
  fsbcode_activated tinyint(4) default '1',
  fsbcode_activated_sig tinyint(4) default '1',
  fsbcode_menu tinyint(4) default '1',
  fsbcode_inline tinyint(4),
  fsbcode_img varchar(100),
  fsbcode_description varchar(255),
  fsbcode_list text,
  fsbcode_order int(11)
);
CREATE INDEX fsb2_fsbcode_fsbcode_id_index ON fsb2_fsbcode (fsbcode_id);
CREATE INDEX fsb2_fsbcode_fsbcode_tag_index ON fsb2_fsbcode (fsbcode_tag);
CREATE TABLE fsb2_groups (
  g_id INTEGER PRIMARY KEY NOT null,
  g_name varchar(255) default '',
  g_desc varchar(255) default '',
  g_type tinyint(4) default '0',
  g_hidden tinyint(4) default '0',
  g_color varchar(255) default '',
  g_open tinyint(4) default '0',
  g_online tinyint(4) default '1',
  g_rank mediumint(9) default '0',
  g_order mediumint(9) default '0'
);
CREATE INDEX fsb2_groups_g_type_index ON fsb2_groups (g_type);
CREATE TABLE fsb2_groups_auth (
  g_id int(11) default '0',
  f_id mediumint(9) default '0',
  ga_view tinyint(4) default '0',
  ga_view_topics tinyint(4) default '0',
  ga_read tinyint(4) default '0',
  ga_create_post tinyint(4) default '0',
  ga_answer_post tinyint(4) default '0',
  ga_create_announce tinyint(4) default '0',
  ga_answer_announce tinyint(4) default '0',
  ga_edit tinyint(4) default '0',
  ga_delete tinyint(4) default '0',
  ga_moderator tinyint(4) default '0',
  ga_create_global_announce tinyint(4) default '0',
  ga_answer_global_announce tinyint(4) default '0'
);
CREATE INDEX fsb2_groups_auth_g_id_index ON fsb2_groups_auth (g_id);
CREATE INDEX fsb2_groups_auth_f_id_index ON fsb2_groups_auth (f_id);
CREATE TABLE fsb2_groups_users (
  g_id int(11) default '0',
  u_id int(11) default '0',
  gu_status tinyint(4) default '0'
);
CREATE INDEX fsb2_groups_users_g_id_index ON fsb2_groups_users (g_id);
CREATE INDEX fsb2_groups_users_u_id_index ON fsb2_groups_users (u_id);
CREATE TABLE fsb2_langs (
  lang_name varchar(5) default '',
  lang_key varchar(100) default '',
  lang_value text
);
CREATE INDEX fsb2_langs_lang_name_index ON fsb2_langs (lang_name);
CREATE INDEX fsb2_langs_lang_key_index ON fsb2_langs (lang_key);
CREATE TABLE fsb2_logs (
  log_id INTEGER PRIMARY KEY NOT null,
  log_type tinyint(4) default '0',
  log_time int(11) default '0',
  log_key varchar(255) default '',
  log_line int(11) default '0',
  log_file varchar(255) default '',
  log_user int(11) default '0',
  log_argv longtext,
  u_id int(11) default '0',
  u_ip varchar(15) default '0'
);
CREATE INDEX fsb2_logs_log_type_index ON fsb2_logs (log_type);
CREATE INDEX fsb2_logs_log_user_index ON fsb2_logs (log_user);
CREATE TABLE fsb2_menu_admin (
  page varchar(255) default '',
  auth tinyint(4) default '0',
  cat varchar(255) default '',
  cat_order smallint(5) default '0',
  page_order smallint(5) default '0',
  page_icon varchar(255),
  module_name varchar(255)
);
CREATE INDEX fsb2_menu_admin_cat_order_index ON fsb2_menu_admin (cat_order);
CREATE INDEX fsb2_menu_admin_page_order_index ON fsb2_menu_admin (page_order);
CREATE TABLE fsb2_mods (
  mod_name varchar(255) default '',
  mod_real_name varchar(255) default '',
  mod_status tinyint(4) default '0',
  mod_version varchar(255) default '',
  mod_description text,
  mod_author varchar(255) default '',
  mod_email varchar(255) default '',
  mod_website varchar(255) default '',
  mod_type tinyint(4) default '0'
);
CREATE INDEX fsb2_mods_mod_type_index ON fsb2_mods (mod_type);
CREATE TABLE fsb2_mp (
  mp_id INTEGER PRIMARY KEY NOT null,
  mp_from int(11) default '0',
  mp_to int(11) default '0',
  mp_title varchar(255) default '',
  mp_content text,
  mp_type tinyint(4) default '0',
  mp_read tinyint(4) default '0',
  mp_time int(11) default '0',
  mp_parent int(11),
  is_auto_answer tinyint(4) default '0',
  u_ip varchar(15) default ''
);
CREATE INDEX fsb2_mp_mp_from_index ON fsb2_mp (mp_from);
CREATE INDEX fsb2_mp_mp_to_index ON fsb2_mp (mp_to);
CREATE TABLE fsb2_mp_blacklist (
  blacklist_id INTEGER PRIMARY KEY NOT null,
  blacklist_from_id int(11) default '0',
  blacklist_to_id int(11) default '0'
);
CREATE INDEX fsb2_mp_blacklist_blacklist_to_id_index ON fsb2_mp_blacklist (blacklist_to_id);
CREATE INDEX fsb2_mp_blacklist_blacklist_from_id_index ON fsb2_mp_blacklist (blacklist_from_id);
CREATE TABLE fsb2_notify (
  notify_id INTEGER PRIMARY KEY NOT null,
  notify_time int(11),
  notify_method tinyint(4),
  notify_subject varchar(255),
  notify_body text,
  notify_bcc longtext,
  notify_try tinyint(4)
);
CREATE INDEX fsb2_notify_notify_time_index ON fsb2_notify (notify_time);
CREATE TABLE fsb2_poll (
  t_id int(11) default '0',
  poll_name varchar(255) default '',
  poll_total_vote int(11) default '0',
  poll_max_vote tinyint(4) default '0'
);
CREATE INDEX fsb2_poll_t_id_index ON fsb2_poll (t_id);
CREATE TABLE fsb2_poll_options (
  poll_opt_id INTEGER PRIMARY KEY NOT null,
  t_id int(11) default '0',
  poll_opt_name varchar(255) default '',
  poll_opt_total int(11) default '0'
);
CREATE INDEX fsb2_poll_options_t_id_index ON fsb2_poll_options (t_id);
CREATE TABLE fsb2_poll_result (
  poll_result_u_id int(11) default '0',
  t_id int(11) default '0'
);
CREATE INDEX fsb2_poll_result_t_id_index ON fsb2_poll_result (t_id);
CREATE INDEX fsb2_poll_result_poll_result_u_id_index ON fsb2_poll_result (poll_result_u_id);
CREATE TABLE fsb2_portail_config (
  portail_module varchar(255),
  portail_name varchar(255) default '',
  portail_value varchar(255) default '',
  portail_functions varchar(255),
  portail_args text,
  portail_type varchar(255)
);
CREATE TABLE fsb2_portail_module (
  pm_name varchar(255) default '',
  pm_position varchar(255) default '',
  pm_order tinyint(4) default '0',
  pm_activ tinyint(4) default '0'
);
CREATE INDEX fsb2_portail_module_pm_order_index ON fsb2_portail_module (pm_order);
CREATE TABLE fsb2_posts (
  p_id INTEGER PRIMARY KEY NOT null,
  f_id mediumint(9) default '0',
  t_id int(11) default '0',
  p_text text,
  p_time int(11) default '0',
  p_nickname varchar(255) default '0',
  u_id int(11) default '0',
  u_ip varchar(15) default '',
  p_edit_user_id int(11) default '0',
  p_edit_time int(11) default '0',
  p_edit_total smallint(6) default '0',
  p_approve tinyint(4) default '0',
  p_map varchar(255)
);
CREATE INDEX fsb2_posts_f_id_index ON fsb2_posts (f_id);
CREATE INDEX fsb2_posts_t_id_index ON fsb2_posts (t_id);
CREATE INDEX fsb2_posts_u_id_index ON fsb2_posts (u_id);
CREATE INDEX fsb2_posts_u_id_index ON fsb2_posts (u_id);
CREATE INDEX fsb2_posts_f_id_index ON fsb2_posts (f_id);
CREATE INDEX fsb2_posts_u_id_index ON fsb2_posts (u_id);
CREATE INDEX fsb2_posts_f_id_index ON fsb2_posts (f_id);
CREATE INDEX fsb2_posts_t_id_index ON fsb2_posts (t_id);
CREATE TABLE fsb2_posts_abuse (
  pa_id INTEGER PRIMARY KEY NOT null,
  p_id int(11) default '0',
  t_id int(11) default '0',
  u_id int(11) default '0',
  pa_parent int(11) default '0',
  pa_text text,
  pa_time int(11) default '0',
  pa_status tinyint(4) default '0',
  pa_mp_id int(11) default '0'
);
CREATE INDEX fsb2_posts_abuse_p_id_index ON fsb2_posts_abuse (p_id);
CREATE INDEX fsb2_posts_abuse_t_id_index ON fsb2_posts_abuse (t_id);
CREATE INDEX fsb2_posts_abuse_u_id_index ON fsb2_posts_abuse (u_id);
CREATE INDEX fsb2_posts_abuse_pa_mp_id_index ON fsb2_posts_abuse (pa_mp_id);
CREATE INDEX fsb2_posts_abuse_pa_parent_index ON fsb2_posts_abuse (pa_parent);
CREATE TABLE fsb2_process (
  process_id mediumint(9) auto_increment,
  process_last_timestamp int(11),
  process_step_timestamp int(11),
  process_function varchar(255),
  process_step_minimum int(11) DEFAULT '0'
);
CREATE INDEX fsb2_process_process_id_index ON fsb2_process (process_id);
CREATE TABLE fsb2_profil_fields (
  pf_id mediumint(9) auto_increment,
  pf_html_type tinyint(4) default '1',
  pf_regexp varchar(255) default '',
  pf_type tinyint(4) default '0',
  pf_lang varchar(255) default '',
  pf_lang_desc varchar(255) default '',
  pf_order mediumint(9) default '0',
  pf_groups text,
  pf_topic tinyint(4) default '0',
  pf_register tinyint(4),
  pf_maxlength smallint(5),
  pf_sizelist tinyint(4),
  pf_list text,
  pf_output varchar(255) default ''
);
CREATE INDEX fsb2_profil_fields_pf_id_index ON fsb2_profil_fields (pf_id);
CREATE INDEX fsb2_profil_fields_pf_order_index ON fsb2_profil_fields (pf_order);
CREATE TABLE fsb2_ranks (
  rank_id mediumint(9) auto_increment,
  rank_name varchar(255) default '',
  rank_img varchar(255) default '',
  rank_quota mediumint(9) default '0',
  rank_special tinyint(4) default '0',
  rank_color varchar(255) default ''
);
CREATE INDEX fsb2_ranks_rank_id_index ON fsb2_ranks (rank_id);
CREATE INDEX fsb2_ranks_rank_quota_index ON fsb2_ranks (rank_quota);
CREATE TABLE fsb2_search_match (
  word_id int(11) default '0',
  p_id int(11) default '0',
  is_title tinyint(4) default '0'
);
CREATE INDEX fsb2_search_match_word_id_index ON fsb2_search_match (word_id);
CREATE INDEX fsb2_search_match_p_id_index ON fsb2_search_match (p_id);
CREATE TABLE fsb2_search_word (
  word_id INTEGER PRIMARY KEY NOT null,
  word_content varchar(40) default ''
);
CREATE TABLE fsb2_sessions (
  s_sid char(32) default '',
  s_id int(11) default '0',
  s_ip varchar(15) default '',
  s_time int(11) default '0',
  s_session_start_time int(11) default '0',
  s_cache text,
  s_forum_access varchar(255) default '',
  s_signal_user int(11) default '0',
  s_visual_code varchar(255) default '',
  s_visual_try tinyint(4) default '0',
  s_bot mediumint(9),
  s_admin_logged tinyint(4),
  s_page varchar(255) default '',
  s_user_agent varchar(255) default ''
);
CREATE INDEX fsb2_sessions_s_sid_index ON fsb2_sessions (s_sid);
CREATE INDEX fsb2_sessions_s_id_index ON fsb2_sessions (s_id);
CREATE INDEX fsb2_sessions_s_time_index ON fsb2_sessions (s_time);
CREATE TABLE fsb2_smilies (
  smiley_id mediumint(9) auto_increment,
  smiley_cat smallint(5),
  smiley_tag varchar(255) default '',
  smiley_name varchar(255) default '',
  smiley_order mediumint(9) default '0'
);
CREATE INDEX fsb2_smilies_smiley_id_index ON fsb2_smilies (smiley_id);
CREATE TABLE fsb2_smilies_cat (
  cat_id smallint(5) auto_increment,
  cat_name varchar(255),
  cat_order smallint(5)
);
CREATE INDEX fsb2_smilies_cat_cat_id_index ON fsb2_smilies_cat (cat_id);
CREATE INDEX fsb2_smilies_cat_cat_order_index ON fsb2_smilies_cat (cat_order);
CREATE TABLE fsb2_sub_procedure (
  procedure_id mediumint(9) auto_increment,
  procedure_name varchar(255),
  procedure_source text,
  procedure_auth tinyint(4) default '2',
  PRIMARY KEY (procedure_id) 
);
CREATE TABLE fsb2_topics (
  t_id INTEGER PRIMARY KEY NOT null,
  f_id mediumint(9) default '0',
  u_id int(11) default '0',
  t_title varchar(120) default '',
  t_total_view int(11) default '0',
  t_total_post int(11) default '0',
  t_time int(11) default '0',
  t_first_p_id int(11) default '0',
  t_last_p_id int(11) default '0',
  t_last_p_time int(11) default '0',
  t_last_u_id int(11) default '0',
  t_last_p_nickname varchar(255) default '',
  t_type tinyint(4) default '0',
  t_status tinyint(4) default '1',
  t_map varchar(255) default '',
  t_trace int(11) default '0',
  t_poll tinyint(4) default '0',
  t_map_first_post tinyint(4) default '0',
  t_description varchar(255) default '',
  t_approve tinyint(4) default '0'
);
CREATE INDEX fsb2_topics_f_id_index ON fsb2_topics (f_id);
CREATE INDEX fsb2_topics_t_last_p_time_index ON fsb2_topics (t_last_p_time);
CREATE TABLE fsb2_topics_notification (
  u_id int(11) default '0',
  t_id int(11) default '0',
  tn_status tinyint(4) default '0'
);
CREATE INDEX fsb2_topics_notification_u_id_index ON fsb2_topics_notification (u_id);
CREATE INDEX fsb2_topics_notification_t_id_index ON fsb2_topics_notification (t_id);
CREATE TABLE fsb2_topics_read (
  u_id int(11) default '0',
  t_id int(11) default '0',
  p_id int(11) default '0',
  tr_last_time int(11) default '0'
);
CREATE INDEX fsb2_topics_read_u_id_index ON fsb2_topics_read (u_id);
CREATE INDEX fsb2_topics_read_t_id_index ON fsb2_topics_read (t_id);
CREATE TABLE fsb2_upload (
  upload_id INTEGER PRIMARY KEY NOT null,
  u_id int(11) default '0',
  upload_filename varchar(255) default '',
  upload_realname varchar(255) default '',
  upload_mimetype varchar(255) default '',
  upload_filesize int(11) default '0',
  upload_time int(11) default '0',
  upload_total int(11) default '0',
  upload_auth tinyint(4) default '0'
);
CREATE INDEX fsb2_upload_u_id_index ON fsb2_upload (u_id);
CREATE TABLE fsb2_users (
  u_id INTEGER PRIMARY KEY NOT null,
  u_auth tinyint(4) default '0',
  u_nickname varchar(40) default '',
  u_email varchar(255) default '',
  u_avatar varchar(255) default '',
  u_signature text,
  u_birthday char(10) default '0000/00/00',
  u_language varchar(255) default '',
  u_tpl varchar(255) default '',
  u_joined int(11) default '0',
  u_last_visit int(11) default '0',
  u_last_read int(11) default '0',
  u_last_read_flag tinyint(4) default '0',
  u_total_post int(11) default '0',
  u_total_topic int(11) default '0',
  u_total_abuse int(11) default '-1',
  u_total_unapproved int(11) default '-1',
  u_total_mp int(11) default '0',
  u_avatar_method tinyint(4) default '0',
  u_can_use_avatar tinyint(4) default '1',
  u_can_use_sig tinyint(4) default '1',
  u_sexe tinyint(4) default '0',
  u_activate_redirection tinyint(4) default '8',
  u_activate_email tinyint(4) default '4',
  u_activate_auto_notification tinyint(4) default '2',
  u_activate_mp_notification tinyint(4) default '1',
  u_activate_hidden tinyint(4) default '0',
  u_activate_fscode tinyint(4) default '6',
  u_activate_avatar tinyint(4) default '1',
  u_activate_sig tinyint(4) default '1',
  u_activate_img tinyint(4) default '6',
  u_activate_ajax tinyint(4) default '1',
  u_activate_userlist tinyint(4) default '1',
  u_mp_auto_answer_activ tinyint(4) default '0',
  u_mp_auto_answer_message varchar(255) default '',
  u_rank_id mediumint(9) default '0',
  u_activate_wysiwyg tinyint(4) default '1',
  u_new_mp tinyint(4) default '0',
  u_newsletter tinyint(4) default '0',
  u_single_group_id int(11) default '0',
  u_default_group_id int(11) default '2',
  u_color varchar(255) default '',
  u_comment text,
  u_register_ip varchar(15),
  u_activated tinyint(4),
  u_confirm_hash char(32) default '',
  u_total_warning tinyint(4) default '0',
  u_warn_post int(11) default '0',
  u_warn_read int(11) default '0',
  u_utc tinyint(4) default '0',
  u_utc_dst tinyint(4),
  u_approve tinyint(4) default '0',
  u_flood_post int(11) default '0',
  u_notepad text
);
CREATE INDEX fsb2_users_u_nickname_index ON fsb2_users (u_nickname);
CREATE INDEX fsb2_users_u_birthday_index ON fsb2_users (u_birthday);
CREATE TABLE fsb2_users_password (
  u_id INTEGER PRIMARY KEY NOT null,
  u_login varchar(255) default '',
  u_password varchar(255) default '',
  u_autologin_key varchar(40) default '',
  u_algorithm varchar(255) default '',
  u_use_salt tinyint(4) default '1'
);
CREATE INDEX fsb2_users_password_u_login_index ON fsb2_users_password (u_login);
CREATE TABLE fsb2_users_contact (
  u_id int(11) default '0'
);
CREATE INDEX fsb2_users_contact_u_id_index ON fsb2_users_contact (u_id);
CREATE TABLE fsb2_users_personal (
  u_id int(11) default '0'
);
CREATE INDEX fsb2_users_personal_u_id_index ON fsb2_users_personal (u_id);
CREATE TABLE fsb2_warn (
  warn_id INTEGER PRIMARY KEY NOT null,
  u_id int(11),
  modo_id int(11),
  warn_type tinyint(4),
  warn_reason text,
  warn_time int(11),
  warn_restriction_post varchar(255),
  warn_restriction_read varchar(255)
);
CREATE INDEX fsb2_warn_u_id_index ON fsb2_warn (u_id);
CREATE INDEX fsb2_warn_warn_time_index ON fsb2_warn (warn_time);

