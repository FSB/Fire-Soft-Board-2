DROP TABLE IF EXISTS fsb2_auths;
CREATE TABLE fsb2_auths (
  auth_name varchar(30) NOT NULL default '',
  auth_level tinyint(4) NOT NULL default '0',
  auth_begin tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (auth_name)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_ban;
CREATE TABLE fsb2_ban (
  ban_id int(11) NOT NULL auto_increment,
  ban_type varchar(255) NOT NULL default '',
  ban_content varchar(255) NOT NULL default '',
  ban_length int(11) NOT NULL default '0',
  ban_reason varchar(255) NOT NULL default '',
  ban_cookie tinyint(4) NOT NULL,
  PRIMARY KEY  (ban_id),
  KEY ban_length (ban_length)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_bots;
CREATE TABLE fsb2_bots (
  bot_id mediumint(9) NOT NULL auto_increment,
  bot_name varchar(255) NOT NULL,
  bot_ip varchar(255) NOT NULL,
  bot_agent varchar(255) NOT NULL,
  bot_last int(11) NOT NULL,
  PRIMARY KEY  (bot_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_cache;
CREATE TABLE fsb2_cache (
  cache_hash varchar(255) NOT NULL,
  cache_type varchar(255) NOT NULL,
  cache_content longtext NOT NULL,
  cache_time int(11) NOT NULL,
  PRIMARY KEY  (cache_hash),
  KEY cache_type (cache_type)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_calendar;
CREATE TABLE fsb2_calendar (
  c_id int(11) NOT NULL auto_increment,
  c_begin int(11) NOT NULL,
  c_end int(11) NOT NULL,
  u_id int(11) NOT NULL,
  c_title varchar(255) NOT NULL,
  c_content text NOT NULL,
  c_approve tinyint(4) NOT NULL,
  c_view tinyint(4) NOT NULL,
  PRIMARY KEY  (c_id),
  KEY c_begin (c_begin, c_end)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_censor;
CREATE TABLE fsb2_censor (
  censor_id mediumint(9) NOT NULL auto_increment,
  censor_word varchar(255) NOT NULL default '',
  censor_replace varchar(255) NOT NULL default '',
  censor_regexp tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (censor_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_config;
CREATE TABLE fsb2_config (
  cfg_name varchar(255) NOT NULL default '',
  cfg_value varchar(255) NOT NULL default '',
  PRIMARY KEY  (cfg_name)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_config_handler;
CREATE TABLE fsb2_config_handler (
  cfg_cat varchar(30) NOT NULL default '',
  cfg_subcat varchar(30) NOT NULL,
  cfg_name varchar(255) NOT NULL default '',
  cfg_function varchar(255) NOT NULL default '',
  cfg_args text NOT NULL,
  cfg_type varchar(255) NOT NULL default '',
  KEY cfg_cat_subcat (cfg_cat, cfg_subcat)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_forums;
CREATE TABLE fsb2_forums (
  f_id mediumint(9) NOT NULL auto_increment,
  f_left mediumint(9) NOT NULL,
  f_right mediumint(9) NOT NULL,
  f_rules text NOT NULL,
  f_cat_id mediumint(9) NOT NULL default '0',
  f_name varchar(255) NOT NULL default '',
  f_text text NOT NULL,
  f_parent mediumint(9) NOT NULL default '0',
  f_status tinyint(4) NOT NULL default '0',
  f_total_topic int(11) NOT NULL default '0',
  f_total_post int(11) NOT NULL default '0',
  f_last_p_id int(11) NOT NULL default '0',
  f_last_t_id int(11) NOT NULL default '0',
  f_last_p_time int(11) NOT NULL default '0',
  f_last_u_id int(11) NOT NULL default '0',
  f_last_p_nickname varchar(255),
  f_last_t_title varchar(255),
  f_level tinyint(4) NOT NULL default '0',
  f_prune_time int(11) NOT NULL default '0',
  f_prune_topic_type varchar(255) NOT NULL default '',
  f_type tinyint(4) NOT NULL default '0',
  f_map_default varchar(255) NOT NULL default '',
  f_map_first_post tinyint(4) NOT NULL default '0',
  f_location varchar(255) NOT NULL default '',
  f_location_view int(11) NOT NULL default '0',
  f_password varchar(255) NOT NULL default '',
  f_tpl varchar(255) NOT NULL default '',
  f_global_announce tinyint(4) NOT NULL default '0',
  f_approve tinyint(4) NOT NULL default '0',
  f_color varchar(255) NOT NULL default '',
  f_display_moderators tinyint(4) NOT NULL default '1',
  f_display_subforums tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (f_id),
  KEY f_right_left (f_left, f_right)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_fsbcode;
CREATE TABLE fsb2_fsbcode (
  fsbcode_id smallint(5) NOT NULL auto_increment,
  fsbcode_tag varchar(20) NOT NULL,
  fsbcode_search text NOT NULL,
  fsbcode_replace text NOT NULL,
  fsbcode_fct varchar(50) NOT NULL,
  fsbcode_priority int(11) NOT NULL,
  fsbcode_wysiwyg tinyint(4) NOT NULL,
  fsbcode_activated tinyint(4) NOT NULL default '1',
  fsbcode_activated_sig tinyint(4) NOT NULL default '1',
  fsbcode_menu tinyint(4) NOT NULL default '1',
  fsbcode_inline tinyint(4) NOT NULL,
  fsbcode_img varchar(100) NOT NULL,
  fsbcode_description varchar(255) NOT NULL,
  fsbcode_list text NOT NULL,
  fsbcode_order int(11) NOT NULL,
  PRIMARY KEY  (fsbcode_id),
  KEY fsbcode_tag (fsbcode_tag)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_groups;
CREATE TABLE fsb2_groups (
  g_id int(11) NOT NULL auto_increment,
  g_name varchar(255) NOT NULL default '',
  g_desc varchar(255) NOT NULL default '',
  g_type tinyint(4) NOT NULL default '0',
  g_hidden tinyint(4) NOT NULL default '0',
  g_color varchar(255) NOT NULL default '',
  g_open tinyint(4) NOT NULL default '0',
  g_online tinyint(4) NOT NULL default '1',
  g_rank mediumint(9) NOT NULL default '0',
  g_order mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (g_id),
  KEY g_type (g_type)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_groups_auth;
CREATE TABLE fsb2_groups_auth (
  g_id int(11) NOT NULL default '0',
  f_id mediumint(9) NOT NULL default '0',
  ga_view tinyint(4) NOT NULL default '0',
  ga_view_topics tinyint(4) NOT NULL default '0',
  ga_read tinyint(4) NOT NULL default '0',
  ga_create_post tinyint(4) NOT NULL default '0',
  ga_answer_post tinyint(4) NOT NULL default '0',
  ga_create_announce tinyint(4) NOT NULL default '0',
  ga_answer_announce tinyint(4) NOT NULL default '0',
  ga_edit tinyint(4) NOT NULL default '0',
  ga_delete tinyint(4) NOT NULL default '0',
  ga_moderator tinyint(4) NOT NULL default '0',
  ga_create_global_announce tinyint(4) NOT NULL default '0',
  ga_answer_global_announce tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (g_id,f_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_groups_users;
CREATE TABLE fsb2_groups_users (
  g_id int(11) NOT NULL default '0',
  u_id int(11) NOT NULL default '0',
  gu_status tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (g_id,u_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_langs;
CREATE TABLE fsb2_langs (
  lang_name varchar(5) NOT NULL default '',
  lang_key varchar(100) NOT NULL default '',
  lang_value text NOT NULL,
  PRIMARY KEY  (lang_name,lang_key)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_logs;
CREATE TABLE fsb2_logs (
  log_id int(11) NOT NULL auto_increment,
  log_type tinyint(4) NOT NULL default '0',
  log_time int(11) NOT NULL default '0',
  log_key varchar(255) NOT NULL default '',
  log_line int(11) NOT NULL default '0',
  log_file varchar(255) NOT NULL default '',
  log_user int(11) NOT NULL default '0',
  log_argv longtext NOT NULL,
  u_id int(11) NOT NULL default '0',
  u_ip varchar(15) NOT NULL default '0',
  PRIMARY KEY  (log_id),
  KEY log_type (log_type),
  KEY log_user (log_user)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_menu_admin;
CREATE TABLE fsb2_menu_admin (
  page varchar(255) NOT NULL default '',
  auth tinyint(4) NOT NULL default '0',
  cat varchar(255) NOT NULL default '',
  cat_order smallint(5) NOT NULL default '0',
  page_order smallint(5) NOT NULL default '0',
  page_icon varchar(255),
  module_name varchar(255) NULL,
  KEY cat_page_order (cat_order, page_order)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_mods;
CREATE TABLE fsb2_mods (
  mod_name varchar(255) NOT NULL default '',
  mod_real_name varchar(255) NOT NULL default '',
  mod_status tinyint(4) NOT NULL default '0',
  mod_version varchar(255) NOT NULL default '',
  mod_description text NOT NULL,
  mod_author varchar(255) NOT NULL default '',
  mod_email varchar(255) NOT NULL default '',
  mod_website varchar(255) NOT NULL default '',
  mod_type tinyint(4) NOT NULL default '0',
  KEY mod_type (mod_type),
  UNIQUE mod_name (mod_name)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_mp;
CREATE TABLE fsb2_mp (
  mp_id int(11) NOT NULL auto_increment,
  mp_from int(11) NOT NULL default '0',
  mp_to int(11) NOT NULL default '0',
  mp_title varchar(255) NOT NULL default '',
  mp_content text NOT NULL,
  mp_type tinyint(4) NOT NULL default '0',
  mp_read tinyint(4) NOT NULL default '0',
  mp_time int(11) NOT NULL default '0',
  mp_parent int(11) NOT NULL,
  is_auto_answer tinyint(4) default '0',
  u_ip varchar(15) NOT NULL default '',
  PRIMARY KEY  (mp_id),
  KEY mp_from (mp_from),
  KEY mp_to (mp_to)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_mp_blacklist;
CREATE TABLE fsb2_mp_blacklist (
  blacklist_id int(11) NOT NULL auto_increment,
  blacklist_from_id int(11) NOT NULL default '0',
  blacklist_to_id int(11) NOT NULL default '0',
  PRIMARY KEY  (blacklist_id),
  KEY blacklist_to_id (blacklist_to_id),
  KEY blacklist_from_id (blacklist_from_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_notify;
CREATE TABLE fsb2_notify (
  notify_id int(11) NOT NULL auto_increment,
  notify_time int(11) NOT NULL,
  notify_method tinyint(4) NOT NULL,
  notify_subject varchar(255) NOT NULL,
  notify_body text NOT NULL,
  notify_bcc longtext NOT NULL,
  notify_try tinyint(4) NOT NULL,
  PRIMARY KEY  (notify_id),
  KEY notify_time (notify_time)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_poll;
CREATE TABLE fsb2_poll (
  t_id int(11) NOT NULL default '0',
  poll_name varchar(255) NOT NULL default '',
  poll_total_vote int(11) NOT NULL default '0',
  poll_max_vote tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (t_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_poll_options;
CREATE TABLE fsb2_poll_options (
  poll_opt_id int(11) NOT NULL auto_increment,
  t_id int(11) NOT NULL default '0',
  poll_opt_name varchar(255) NOT NULL default '',
  poll_opt_total int(11) NOT NULL default '0',
  PRIMARY KEY  (poll_opt_id),
  KEY t_id (t_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_poll_result;
CREATE TABLE fsb2_poll_result (
  poll_result_u_id int(11) NOT NULL default '0',
  t_id int(11) NOT NULL default '0',
  KEY t_id (t_id),
  KEY poll_result_u_id (poll_result_u_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_portail_config;
CREATE TABLE fsb2_portail_config (
  portail_module varchar(255) NOT NULL,
  portail_name varchar(255) NOT NULL default '',
  portail_value varchar(255) NOT NULL default '',
  portail_functions varchar(255) NOT NULL,
  portail_args text NOT NULL,
  portail_type varchar(255) NOT NULL
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_portail_module;
CREATE TABLE fsb2_portail_module (
  pm_name varchar(255) NOT NULL default '',
  pm_position varchar(255) NOT NULL default '',
  pm_order tinyint(4) NOT NULL default '0',
  pm_activ tinyint(4) NOT NULL default '0',
  KEY pm_order (pm_order),
  UNIQUE pm_name (pm_name)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_posts;
CREATE TABLE fsb2_posts (
  p_id int(11) NOT NULL auto_increment,
  f_id mediumint(9) NOT NULL default '0',
  t_id int(11) NOT NULL default '0',
  p_text text NOT NULL,
  p_time int(11) NOT NULL default '0',
  p_nickname varchar(255) NOT NULL default '0',
  u_id int(11) NOT NULL default '0',
  u_ip varchar(15) NOT NULL default '',
  p_edit_user_id int(11) NOT NULL default '0',
  p_edit_time int(11) NOT NULL default '0',
  p_edit_total smallint(6) NOT NULL default '0',
  p_approve tinyint(4) NOT NULL default '0',
  p_map varchar(255),
  PRIMARY KEY  (p_id),
  KEY f_id (f_id),
  KEY t_id (t_id),
  KEY u_id (u_id),
  KEY f_per_user (u_id, f_id),
  KEY t_per_user (u_id, f_id, t_id),
  FULLTEXT KEY p_text (p_text)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_posts_abuse;
CREATE TABLE fsb2_posts_abuse (
  pa_id int(11) NOT NULL auto_increment,
  p_id int(11) NOT NULL default '0',
  t_id int(11) NOT NULL default '0',
  u_id int(11) NOT NULL default '0',
  pa_parent int(11) NOT NULL default '0',
  pa_text text NOT NULL,
  pa_time int(11) NOT NULL default '0',
  pa_status tinyint(4) NOT NULL default '0',
  pa_mp_id int(11) NOT NULL default '0',
  PRIMARY KEY  (pa_id),
  KEY p_id (p_id),
  KEY t_id (t_id),
  KEY u_id (u_id),
  KEY pa_mp_id (pa_mp_id),
  KEY pa_parent (pa_parent)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_process;
CREATE TABLE fsb2_process (
  process_id mediumint(9) NOT NULL auto_increment,
  process_last_timestamp int(11) NOT NULL,
  process_step_timestamp int(11) NOT NULL,
  process_function varchar(255) NOT NULL,
  process_step_minimum int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (process_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_profil_fields;
CREATE TABLE fsb2_profil_fields (
  pf_id mediumint(9) NOT NULL auto_increment,
  pf_html_type tinyint(4) NOT NULL default '1',
  pf_regexp varchar(255) NOT NULL default '',
  pf_type tinyint(4) NOT NULL default '0',
  pf_lang varchar(255) NOT NULL default '',
  pf_lang_desc varchar(255) NOT NULL default '',
  pf_order mediumint(9) NOT NULL default '0',
  pf_groups text NOT NULL,
  pf_topic tinyint(4) NOT NULL default '0',
  pf_register tinyint(4) NOT NULL,
  pf_maxlength smallint(5) unsigned NOT NULL,
  pf_sizelist tinyint(4) NOT NULL,
  pf_list text NOT NULL,
  pf_output varchar(255) NOT NULL default '',
  PRIMARY KEY  (pf_id),
  KEY pf_order (pf_order)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_ranks;
CREATE TABLE fsb2_ranks (
  rank_id mediumint(9) NOT NULL auto_increment,
  rank_name varchar(255) NOT NULL default '',
  rank_img varchar(255) NOT NULL default '',
  rank_quota mediumint(9) NOT NULL default '0',
  rank_special tinyint(4) NOT NULL default '0',
  rank_color varchar(255) NOT NULL default '',
  PRIMARY KEY  (rank_id),
  KEY rank_quota (rank_quota)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_search_match;
CREATE TABLE fsb2_search_match (
  word_id int(11) NOT NULL default '0',
  p_id int(11) NOT NULL default '0',
  is_title tinyint(4) NOT NULL default '0',
  KEY word_id (word_id),
  KEY p_id (p_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_search_word;
CREATE TABLE fsb2_search_word (
  word_id int(11) NOT NULL auto_increment,
  word_content varchar(40) NOT NULL default '',
  PRIMARY KEY (word_id),
  UNIQUE word_content (word_content)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_sessions;
CREATE TABLE fsb2_sessions (
  s_sid char(32) NOT NULL default '',
  s_id int(11) NOT NULL default '0',
  s_ip varchar(15) NOT NULL default '',
  s_time int(11) NOT NULL default '0',
  s_session_start_time int(11) NOT NULL default '0',
  s_cache text NOT NULL,
  s_forum_access varchar(255) NOT NULL default '',
  s_signal_user int(11) NOT NULL default '0',
  s_visual_code varchar(255) NOT NULL default '',
  s_visual_try tinyint(4) NOT NULL default '0',
  s_bot mediumint(9) NOT NULL,
  s_admin_logged tinyint(4) NOT NULL,
  s_page varchar(255) NOT NULL default '',
  s_user_agent varchar(255) NOT NULL default '',
  PRIMARY KEY  (s_sid),
  KEY s_id (s_id),
  KEY s_time (s_time)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_smilies;
CREATE TABLE fsb2_smilies (
  smiley_id mediumint(9) NOT NULL auto_increment,
  smiley_cat smallint(5) NOT NULL,
  smiley_tag varchar(255) NOT NULL default '',
  smiley_name varchar(255) NOT NULL default '',
  smiley_order mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (smiley_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_smilies_cat;
CREATE TABLE fsb2_smilies_cat (
  cat_id smallint(5) NOT NULL auto_increment,
  cat_name varchar(255) NOT NULL,
  cat_order smallint(5) NOT NULL,
  PRIMARY KEY  (cat_id),
  KEY cat_order (cat_order)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_sub_procedure;
CREATE TABLE fsb2_sub_procedure (
  procedure_id mediumint(9) NOT NULL auto_increment,
  procedure_name varchar(255) NOT NULL,
  procedure_source text NOT NULL,
  procedure_auth tinyint(4) NOT NULL default '2',
  PRIMARY KEY (procedure_id) 
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_topics;
CREATE TABLE fsb2_topics (
  t_id int(11) NOT NULL auto_increment,
  f_id mediumint(9) NOT NULL default '0',
  u_id int(11) NOT NULL default '0',
  t_title varchar(120) NOT NULL default '',
  t_total_view int(11) NOT NULL default '0',
  t_total_post int(11) NOT NULL default '0',
  t_time int(11) NOT NULL default '0',
  t_first_p_id int(11) NOT NULL default '0',
  t_last_p_id int(11) NOT NULL default '0',
  t_last_p_time int(11) NOT NULL default '0',
  t_last_u_id int(11) NOT NULL default '0',
  t_last_p_nickname varchar(255) NOT NULL default '',
  t_type tinyint(4) NOT NULL default '0',
  t_status tinyint(4) NOT NULL default '1',
  t_map varchar(255) NOT NULL default '',
  t_trace int(11) NOT NULL default '0',
  t_poll tinyint(4) NOT NULL default '0',
  t_map_first_post tinyint(4) NOT NULL default '0',
  t_description varchar(255) NOT NULL default '',
  t_approve tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (t_id),
  KEY f_id (f_id),
  KEY t_last_p_time (t_last_p_time),
  FULLTEXT KEY t_title (t_title)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_topics_notification;
CREATE TABLE fsb2_topics_notification (
  u_id int(11) NOT NULL default '0',
  t_id int(11) NOT NULL default '0',
  tn_status tinyint(4) NOT NULL default '0',
  PRIMARY KEY t_u_id (u_id,t_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_topics_read;
CREATE TABLE fsb2_topics_read (
  u_id int(11) NOT NULL default '0',
  t_id int(11) NOT NULL default '0',
  p_id int(11) NOT NULL default '0',
  tr_last_time int(11) NOT NULL default '0',
  PRIMARY KEY t_u_id (u_id,t_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_upload;
CREATE TABLE fsb2_upload (
  upload_id int(11) NOT NULL auto_increment,
  u_id int(11) NOT NULL default '0',
  upload_filename varchar(255) NOT NULL default '',
  upload_realname varchar(255) NOT NULL default '',
  upload_mimetype varchar(255) NOT NULL default '',
  upload_filesize int(11) NOT NULL default '0',
  upload_time int(11) NOT NULL default '0',
  upload_total int(11) NOT NULL default '0',
  upload_auth tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (upload_id),
  KEY u_id (u_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_users;
CREATE TABLE fsb2_users (
  u_id int(11) NOT NULL auto_increment,
  u_auth tinyint(4) NOT NULL default '0',
  u_nickname varchar(40) NOT NULL default '',
  u_email varchar(255) NOT NULL default '',
  u_avatar varchar(255) NOT NULL default '',
  u_signature text NOT NULL,
  u_birthday char(10) NOT NULL default '0000/00/00',
  u_language varchar(255) NOT NULL default '',
  u_tpl varchar(255) NOT NULL default '',
  u_joined int(11) NOT NULL default '0',
  u_last_visit int(11) NOT NULL default '0',
  u_last_read int(11) NOT NULL default '0',
  u_last_read_flag tinyint(4) NOT NULL default '0',
  u_total_post int(11) NOT NULL default '0',
  u_total_topic int(11) NOT NULL default '0',
  u_total_abuse int(11) NOT NULL default '-1',
  u_total_unapproved int(11) NOT NULL default '-1',
  u_total_mp int(11) NOT NULL default '0',
  u_avatar_method tinyint(4) NOT NULL default '0',
  u_can_use_avatar tinyint(4) NOT NULL default '1',
  u_can_use_sig tinyint(4) NOT NULL default '1',
  u_sexe tinyint(4) NOT NULL default '0',
  u_activate_redirection tinyint(4) NOT NULL default '8',
  u_activate_email tinyint(4) NOT NULL default '4',
  u_activate_auto_notification tinyint(4) NOT NULL default '2',
  u_activate_mp_notification tinyint(4) NOT NULL default '1',
  u_activate_hidden tinyint(4) NOT NULL default '0',
  u_activate_fscode tinyint(4) NOT NULL default '6',
  u_activate_avatar tinyint(4) NOT NULL default '1',
  u_activate_sig tinyint(4) NOT NULL default '1',
  u_activate_img tinyint(4) NOT NULL default '6',
  u_activate_ajax tinyint(4) NOT NULL default '1',
  u_activate_userlist tinyint(4) NOT NULL default '1',
  u_mp_auto_answer_activ tinyint(4) NOT NULL default '0',
  u_mp_auto_answer_message varchar(255) NOT NULL default '',
  u_rank_id mediumint(9) NOT NULL default '0',
  u_activate_wysiwyg tinyint(4) NOT NULL default '1',
  u_new_mp tinyint(4) NOT NULL default '0',
  u_newsletter tinyint(4) NOT NULL default '0',
  u_single_group_id int(11) NOT NULL default '0',
  u_default_group_id int(11) NOT NULL default '2',
  u_color varchar(255) NOT NULL default '',
  u_comment text NOT NULL,
  u_register_ip varchar(15) NOT NULL,
  u_activated tinyint(4) NOT NULL,
  u_confirm_hash char(32) NOT NULL default '',
  u_total_warning tinyint(4) NOT NULL default '0',
  u_warn_post int(11) NOT NULL default '0',
  u_warn_read int(11) NOT NULL default '0',
  u_utc tinyint(4) NOT NULL default '0',
  u_utc_dst tinyint(4) NOT NULL,
  u_approve tinyint(4) NOT NULL default '0',
  u_flood_post int(11) NOT NULL default '0',
  u_notepad text NOT NULL,
  PRIMARY KEY  (u_id),
  KEY u_nickname (u_nickname),
  KEY u_birthday (u_birthday)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_users_password;
CREATE TABLE fsb2_users_password (
  u_id int(11) NOT NULL auto_increment,
  u_login varchar(255) NOT NULL default '',
  u_password varchar(255) NOT NULL default '',
  u_autologin_key varchar(40) NOT NULL default '',
  u_algorithm varchar(255) NOT NULL default '',
  u_use_salt tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (u_id),
  KEY u_login (u_login),
  UNIQUE u_autologin_key (u_autologin_key)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_users_contact;
CREATE TABLE fsb2_users_contact (
  u_id int(11) NOT NULL default '0',
  PRIMARY KEY  (u_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_users_personal;
CREATE TABLE fsb2_users_personal (
  u_id int(11) NOT NULL default '0',
  PRIMARY KEY  (u_id)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

DROP TABLE IF EXISTS fsb2_warn;
CREATE TABLE fsb2_warn (
  warn_id int(11) NOT NULL auto_increment,
  u_id int(11) NOT NULL,
  modo_id int(11) NOT NULL,
  warn_type tinyint(4) NOT NULL,
  warn_reason text NOT NULL,
  warn_time int(11) NOT NULL,
  warn_restriction_post varchar(255) NOT NULL,
  warn_restriction_read varchar(255) NOT NULL,
  PRIMARY KEY  (warn_id),
  KEY u_id (u_id),
  KEY warn_time (warn_time)
) Engine=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

