DROP TABLE IF EXISTS fsb2_auths;
CREATE TABLE fsb2_auths (
  auth_name varchar(30) NOT NULL default '',
  auth_level INT2 NOT NULL default '0',
  auth_begin INT2 NOT NULL default '0',
  PRIMARY KEY (auth_name)
);
DROP TABLE IF EXISTS fsb2_ban;
CREATE SEQUENCE fsb2_ban_seq;
CREATE TABLE fsb2_ban (
ban_id INT DEFAULT nextval('fsb2_ban_seq'),
  ban_type varchar(255) default '',
  ban_content varchar(255) default '',
  ban_length INT4 default '0',
  ban_reason varchar(255) default '',
  ban_cookie INT2,
  PRIMARY KEY (ban_id)
);
CREATE INDEX fsb2_ban_ban_length_index ON fsb2_ban (ban_length);
DROP TABLE IF EXISTS fsb2_bots;
CREATE SEQUENCE fsb2_bots_seq;
CREATE TABLE fsb2_bots (
bot_id INT DEFAULT nextval('fsb2_bots_seq'),
  bot_name varchar(255),
  bot_ip varchar(255),
  bot_agent varchar(255),
  bot_last INT4,
  PRIMARY KEY (bot_id)
);
DROP TABLE IF EXISTS fsb2_cache;
CREATE TABLE fsb2_cache (
  cache_hash varchar(255) NOT NULL,
  cache_type varchar(255) NOT NULL,
  cache_content text
					 NOT NULL,
  cache_time INT4 NOT NULL,
  PRIMARY KEY (cache_hash)
);
CREATE INDEX fsb2_cache_cache_type_index ON fsb2_cache (cache_type);
DROP TABLE IF EXISTS fsb2_calendar;
CREATE SEQUENCE fsb2_calendar_seq;
CREATE TABLE fsb2_calendar (
c_id INT DEFAULT nextval('fsb2_calendar_seq'),
  c_begin INT4,
  c_end INT4,
  u_id INT4,
  c_title varchar(255),
  c_content text,
  c_approve INT2,
  c_view INT2,
  PRIMARY KEY (c_id)
);
CREATE INDEX fsb2_calendar_c_begin_index ON fsb2_calendar (c_begin, c_end);
DROP TABLE IF EXISTS fsb2_censor;
CREATE SEQUENCE fsb2_censor_seq;
CREATE TABLE fsb2_censor (
censor_id INT DEFAULT nextval('fsb2_censor_seq'),
  censor_word varchar(255) default '',
  censor_replace varchar(255) default '',
  censor_regexp INT2 default '0',
  PRIMARY KEY (censor_id)
);
DROP TABLE IF EXISTS fsb2_config;
CREATE TABLE fsb2_config (
  cfg_name varchar(255) NOT NULL default '',
  cfg_value varchar(255) NOT NULL default '',
  PRIMARY KEY (cfg_name)
);
DROP TABLE IF EXISTS fsb2_config_handler;
CREATE TABLE fsb2_config_handler (
  cfg_cat varchar(30) NOT NULL default '',
  cfg_subcat varchar(30) NOT NULL,
  cfg_name varchar(255) NOT NULL default '',
  cfg_function varchar(255) NOT NULL default '',
  cfg_args text NOT NULL,
  cfg_type varchar(255) NOT NULL default ''
);
CREATE INDEX fsb2_config_handler_cfg_cat_subcat_index ON fsb2_config_handler (cfg_cat, cfg_subcat);
DROP TABLE IF EXISTS fsb2_forums;
CREATE SEQUENCE fsb2_forums_seq;
CREATE TABLE fsb2_forums (
f_id INT DEFAULT nextval('fsb2_forums_seq'),
  f_left INT4,
  f_right INT4,
  f_rules text,
  f_cat_id INT4 default '0',
  f_name varchar(255) default '',
  f_text text,
  f_parent INT4 default '0',
  f_status INT2 default '0',
  f_total_topic INT4 default '0',
  f_total_post INT4 default '0',
  f_last_p_id INT4 default '0',
  f_last_t_id INT4 default '0',
  f_last_p_time INT4 default '0',
  f_last_u_id INT4 default '0',
  f_last_p_nickname varchar(255),
  f_last_t_title varchar(255),
  f_level INT2 default '0',
  f_prune_time INT4 default '0',
  f_prune_topic_type varchar(255) default '',
  f_type INT2 default '0',
  f_map_default varchar(255) default '',
  f_map_first_post INT2 default '0',
  f_location varchar(255) default '',
  f_location_view INT4 default '0',
  f_password varchar(255) default '',
  f_tpl varchar(255) default '',
  f_global_announce INT2 default '0',
  f_approve INT2 default '0',
  f_color varchar(255) default '',
  f_display_moderators INT2 default '1',
  f_display_subforums INT2 default '1',
  PRIMARY KEY (f_id)
);
CREATE INDEX fsb2_forums_f_right_left_index ON fsb2_forums (f_left, f_right);
DROP TABLE IF EXISTS fsb2_fsbcode;
CREATE SEQUENCE fsb2_fsbcode_seq;
CREATE TABLE fsb2_fsbcode (
fsbcode_id INT DEFAULT nextval('fsb2_fsbcode_seq'),
  fsbcode_tag varchar(20),
  fsbcode_search text,
  fsbcode_replace text,
  fsbcode_fct varchar(50),
  fsbcode_priority INT4,
  fsbcode_wysiwyg INT2,
  fsbcode_activated INT2 default '1',
  fsbcode_activated_sig INT2 default '1',
  fsbcode_menu INT2 default '1',
  fsbcode_inline INT2,
  fsbcode_img varchar(100),
  fsbcode_description varchar(255),
  fsbcode_list text,
  fsbcode_order INT4,
  PRIMARY KEY (fsbcode_id)
);
CREATE INDEX fsb2_fsbcode_fsbcode_tag_index ON fsb2_fsbcode (fsbcode_tag);
DROP TABLE IF EXISTS fsb2_groups;
CREATE SEQUENCE fsb2_groups_seq;
CREATE TABLE fsb2_groups (
g_id INT DEFAULT nextval('fsb2_groups_seq'),
  g_name varchar(255) default '',
  g_desc varchar(255) default '',
  g_type INT2 default '0',
  g_hidden INT2 default '0',
  g_color varchar(255) default '',
  g_open INT2 default '0',
  g_online INT2 default '1',
  g_rank INT4 default '0',
  g_order INT4 default '0',
  PRIMARY KEY (g_id)
);
CREATE INDEX fsb2_groups_g_type_index ON fsb2_groups (g_type);
DROP TABLE IF EXISTS fsb2_groups_auth;
CREATE TABLE fsb2_groups_auth (
  g_id INT4 NOT NULL default '0',
  f_id INT4 NOT NULL default '0',
  ga_view INT2 NOT NULL default '0',
  ga_view_topics INT2 NOT NULL default '0',
  ga_read INT2 NOT NULL default '0',
  ga_create_post INT2 NOT NULL default '0',
  ga_answer_post INT2 NOT NULL default '0',
  ga_create_announce INT2 NOT NULL default '0',
  ga_answer_announce INT2 NOT NULL default '0',
  ga_edit INT2 NOT NULL default '0',
  ga_delete INT2 NOT NULL default '0',
  ga_moderator INT2 NOT NULL default '0',
  ga_create_global_announce INT2 NOT NULL default '0',
  ga_answer_global_announce INT2 NOT NULL default '0',
  PRIMARY KEY (g_id,f_id)
);
DROP TABLE IF EXISTS fsb2_groups_users;
CREATE TABLE fsb2_groups_users (
  g_id INT4 NOT NULL default '0',
  u_id INT4 NOT NULL default '0',
  gu_status INT2 NOT NULL default '0',
  PRIMARY KEY (g_id,u_id)
);
DROP TABLE IF EXISTS fsb2_langs;
CREATE TABLE fsb2_langs (
  lang_name varchar(5) NOT NULL default '',
  lang_key varchar(100) NOT NULL default '',
  lang_value text NOT NULL,
  PRIMARY KEY (lang_name,lang_key)
);
DROP TABLE IF EXISTS fsb2_logs;
CREATE SEQUENCE fsb2_logs_seq;
CREATE TABLE fsb2_logs (
log_id INT DEFAULT nextval('fsb2_logs_seq'),
  log_type INT2 default '0',
  log_time INT4 default '0',
  log_key varchar(255) default '',
  log_line INT4 default '0',
  log_file varchar(255) default '',
  log_user INT4 default '0',
  log_argv text
					,
  u_id INT4 default '0',
  u_ip varchar(15) default '0',
  PRIMARY KEY (log_id)
);
CREATE INDEX fsb2_logs_log_type_index ON fsb2_logs (log_type);
CREATE INDEX fsb2_logs_log_user_index ON fsb2_logs (log_user);
DROP TABLE IF EXISTS fsb2_menu_admin;
CREATE TABLE fsb2_menu_admin (
  page varchar(255) NOT NULL default '',
  auth INT2 NOT NULL default '0',
  cat varchar(255) NOT NULL default '',
  cat_order INT4 NOT NULL default '0',
  page_order INT4 NOT NULL default '0',
  page_icon varchar(255),
  module_name varchar(255) NULL
);
CREATE INDEX fsb2_menu_admin_cat_page_order_index ON fsb2_menu_admin (cat_order, page_order);
DROP TABLE IF EXISTS fsb2_mods;
CREATE TABLE fsb2_mods (
  mod_name varchar(255) NOT NULL default '',
  mod_real_name varchar(255) NOT NULL default '',
  mod_status INT2 NOT NULL default '0',
  mod_version varchar(255) NOT NULL default '',
  mod_description text NOT NULL,
  mod_author varchar(255) NOT NULL default '',
  mod_email varchar(255) NOT NULL default '',
  mod_website varchar(255) NOT NULL default '',
  mod_type INT2 NOT NULL default '0'
);
CREATE INDEX fsb2_mods_mod_type_index ON fsb2_mods (mod_type);
CREATE UNIQUE INDEX fsb2_mods_mod_name_index ON fsb2_mods (mod_name);
DROP TABLE IF EXISTS fsb2_mp;
CREATE SEQUENCE fsb2_mp_seq;
CREATE TABLE fsb2_mp (
mp_id INT DEFAULT nextval('fsb2_mp_seq'),
  mp_from INT4 default '0',
  mp_to INT4 default '0',
  mp_title varchar(255) default '',
  mp_content text,
  mp_type INT2 default '0',
  mp_read INT2 default '0',
  mp_time INT4 default '0',
  mp_parent INT4,
  is_auto_answer INT2 default '0',
  u_ip varchar(15) default '',
  PRIMARY KEY (mp_id)
);
CREATE INDEX fsb2_mp_mp_from_index ON fsb2_mp (mp_from);
CREATE INDEX fsb2_mp_mp_to_index ON fsb2_mp (mp_to);
DROP TABLE IF EXISTS fsb2_mp_blacklist;
CREATE SEQUENCE fsb2_mp_blacklist_seq;
CREATE TABLE fsb2_mp_blacklist (
blacklist_id INT DEFAULT nextval('fsb2_mp_blacklist_seq'),
  blacklist_from_id INT4 default '0',
  blacklist_to_id INT4 default '0',
  PRIMARY KEY (blacklist_id)
);
CREATE INDEX fsb2_mp_blacklist_blacklist_to_id_index ON fsb2_mp_blacklist (blacklist_to_id);
CREATE INDEX fsb2_mp_blacklist_blacklist_from_id_index ON fsb2_mp_blacklist (blacklist_from_id);
DROP TABLE IF EXISTS fsb2_notify;
CREATE SEQUENCE fsb2_notify_seq;
CREATE TABLE fsb2_notify (
notify_id INT DEFAULT nextval('fsb2_notify_seq'),
  notify_time INT4,
  notify_method INT2,
  notify_subject varchar(255),
  notify_body text,
  notify_bcc text
					,
  notify_try INT2,
  PRIMARY KEY (notify_id)
);
CREATE INDEX fsb2_notify_notify_time_index ON fsb2_notify (notify_time);
DROP TABLE IF EXISTS fsb2_poll;
CREATE TABLE fsb2_poll (
  t_id INT4 NOT NULL default '0',
  poll_name varchar(255) NOT NULL default '',
  poll_total_vote INT4 NOT NULL default '0',
  poll_max_vote INT2 NOT NULL default '0',
  PRIMARY KEY (t_id)
);
DROP TABLE IF EXISTS fsb2_poll_options;
CREATE SEQUENCE fsb2_poll_options_seq;
CREATE TABLE fsb2_poll_options (
poll_opt_id INT DEFAULT nextval('fsb2_poll_options_seq'),
  t_id INT4 default '0',
  poll_opt_name varchar(255) default '',
  poll_opt_total INT4 default '0',
  PRIMARY KEY (poll_opt_id)
);
CREATE INDEX fsb2_poll_options_t_id_index ON fsb2_poll_options (t_id);
DROP TABLE IF EXISTS fsb2_poll_result;
CREATE TABLE fsb2_poll_result (
  poll_result_u_id INT4 NOT NULL default '0',
  t_id INT4 NOT NULL default '0'
);
CREATE INDEX fsb2_poll_result_t_id_index ON fsb2_poll_result (t_id);
CREATE INDEX fsb2_poll_result_poll_result_u_id_index ON fsb2_poll_result (poll_result_u_id);
DROP TABLE IF EXISTS fsb2_portail_config;
CREATE TABLE fsb2_portail_config (
  portail_module varchar(255) NOT NULL,
  portail_name varchar(255) NOT NULL default '',
  portail_value varchar(255) NOT NULL default '',
  portail_functions varchar(255) NOT NULL,
  portail_args text NOT NULL,
  portail_type varchar(255) NOT NULL
);
DROP TABLE IF EXISTS fsb2_portail_module;
CREATE TABLE fsb2_portail_module (
  pm_name varchar(255) NOT NULL default '',
  pm_position varchar(255) NOT NULL default '',
  pm_order INT2 NOT NULL default '0',
  pm_activ INT2 NOT NULL default '0'
);
CREATE INDEX fsb2_portail_module_pm_order_index ON fsb2_portail_module (pm_order);
CREATE UNIQUE INDEX fsb2_portail_module_pm_name_index ON fsb2_portail_module (pm_name);
DROP TABLE IF EXISTS fsb2_posts;
CREATE SEQUENCE fsb2_posts_seq;
CREATE TABLE fsb2_posts (
p_id INT DEFAULT nextval('fsb2_posts_seq'),
  f_id INT4 default '0',
  t_id INT4 default '0',
  p_text text,
  p_time INT4 default '0',
  p_nickname varchar(255) default '0',
  u_id INT4 default '0',
  u_ip varchar(15) default '',
  p_edit_user_id INT4 default '0',
  p_edit_time INT4 default '0',
  p_edit_total INT4 default '0',
  p_approve INT2 default '0',
  p_map varchar(255),
  PRIMARY KEY (p_id)
);
CREATE INDEX fsb2_posts_f_id_index ON fsb2_posts (f_id);
CREATE INDEX fsb2_posts_t_id_index ON fsb2_posts (t_id);
CREATE INDEX fsb2_posts_u_id_index ON fsb2_posts (u_id);
CREATE INDEX fsb2_posts_f_per_user_index ON fsb2_posts (u_id, f_id);
CREATE INDEX fsb2_posts_t_per_user_index ON fsb2_posts (u_id, f_id, t_id);
DROP TABLE IF EXISTS fsb2_posts_abuse;
CREATE SEQUENCE fsb2_posts_abuse_seq;
CREATE TABLE fsb2_posts_abuse (
pa_id INT DEFAULT nextval('fsb2_posts_abuse_seq'),
  p_id INT4 default '0',
  t_id INT4 default '0',
  u_id INT4 default '0',
  pa_parent INT4 default '0',
  pa_text text,
  pa_time INT4 default '0',
  pa_status INT2 default '0',
  pa_mp_id INT4 default '0',
  PRIMARY KEY (pa_id)
);
CREATE INDEX fsb2_posts_abuse_p_id_index ON fsb2_posts_abuse (p_id);
CREATE INDEX fsb2_posts_abuse_t_id_index ON fsb2_posts_abuse (t_id);
CREATE INDEX fsb2_posts_abuse_u_id_index ON fsb2_posts_abuse (u_id);
CREATE INDEX fsb2_posts_abuse_pa_mp_id_index ON fsb2_posts_abuse (pa_mp_id);
CREATE INDEX fsb2_posts_abuse_pa_parent_index ON fsb2_posts_abuse (pa_parent);
DROP TABLE IF EXISTS fsb2_process;
CREATE SEQUENCE fsb2_process_seq;
CREATE TABLE fsb2_process (
process_id INT DEFAULT nextval('fsb2_process_seq'),
  process_last_timestamp INT4,
  process_step_timestamp INT4,
  process_function varchar(255),
  process_step_minimum INT4 DEFAULT '0',
  PRIMARY KEY (process_id)
);
DROP TABLE IF EXISTS fsb2_profil_fields;
CREATE SEQUENCE fsb2_profil_fields_seq;
CREATE TABLE fsb2_profil_fields (
pf_id INT DEFAULT nextval('fsb2_profil_fields_seq'),
  pf_html_type INT2 default '1',
  pf_regexp varchar(255) default '',
  pf_type INT2 default '0',
  pf_lang varchar(255) default '',
  pf_lang_desc varchar(255) default '',
  pf_order INT4 default '0',
  pf_groups text,
  pf_topic INT2 default '0',
  pf_register INT2,
  pf_maxlength INT4,
  pf_sizelist INT2,
  pf_list text,
  pf_output varchar(255) default '',
  PRIMARY KEY (pf_id)
);
CREATE INDEX fsb2_profil_fields_pf_order_index ON fsb2_profil_fields (pf_order);
DROP TABLE IF EXISTS fsb2_ranks;
CREATE SEQUENCE fsb2_ranks_seq;
CREATE TABLE fsb2_ranks (
rank_id INT DEFAULT nextval('fsb2_ranks_seq'),
  rank_name varchar(255) default '',
  rank_img varchar(255) default '',
  rank_quota INT4 default '0',
  rank_special INT2 default '0',
  rank_color varchar(255) default '',
  PRIMARY KEY (rank_id)
);
CREATE INDEX fsb2_ranks_rank_quota_index ON fsb2_ranks (rank_quota);
DROP TABLE IF EXISTS fsb2_search_match;
CREATE TABLE fsb2_search_match (
  word_id INT4 NOT NULL default '0',
  p_id INT4 NOT NULL default '0',
  is_title INT2 NOT NULL default '0'
);
CREATE INDEX fsb2_search_match_word_id_index ON fsb2_search_match (word_id);
CREATE INDEX fsb2_search_match_p_id_index ON fsb2_search_match (p_id);
DROP TABLE IF EXISTS fsb2_search_word;
CREATE SEQUENCE fsb2_search_word_seq;
CREATE TABLE fsb2_search_word (
word_id INT DEFAULT nextval('fsb2_search_word_seq'),
  word_content varchar(40) default '',
  PRIMARY KEY (word_id)
);
CREATE UNIQUE INDEX fsb2_search_word_word_content_index ON fsb2_search_word (word_content);
DROP TABLE IF EXISTS fsb2_sessions;
CREATE TABLE fsb2_sessions (
  s_sid char(32) NOT NULL default '',
  s_id INT4 NOT NULL default '0',
  s_ip varchar(15) NOT NULL default '',
  s_time INT4 NOT NULL default '0',
  s_session_start_time INT4 NOT NULL default '0',
  s_cache text NOT NULL,
  s_forum_access varchar(255) NOT NULL default '',
  s_signal_user INT4 NOT NULL default '0',
  s_visual_code varchar(255) NOT NULL default '',
  s_visual_try INT2 NOT NULL default '0',
  s_bot INT4 NOT NULL,
  s_admin_logged INT2 NOT NULL,
  s_page varchar(255) NOT NULL default '',
  s_user_agent varchar(255) NOT NULL default '',
  PRIMARY KEY (s_sid)
);
CREATE INDEX fsb2_sessions_s_id_index ON fsb2_sessions (s_id);
CREATE INDEX fsb2_sessions_s_time_index ON fsb2_sessions (s_time);
DROP TABLE IF EXISTS fsb2_smilies;
CREATE SEQUENCE fsb2_smilies_seq;
CREATE TABLE fsb2_smilies (
smiley_id INT DEFAULT nextval('fsb2_smilies_seq'),
  smiley_cat INT4,
  smiley_tag varchar(255) default '',
  smiley_name varchar(255) default '',
  smiley_order INT4 default '0',
  PRIMARY KEY (smiley_id)
);
DROP TABLE IF EXISTS fsb2_smilies_cat;
CREATE SEQUENCE fsb2_smilies_cat_seq;
CREATE TABLE fsb2_smilies_cat (
cat_id INT DEFAULT nextval('fsb2_smilies_cat_seq'),
  cat_name varchar(255),
  cat_order INT4,
  PRIMARY KEY (cat_id)
);
CREATE INDEX fsb2_smilies_cat_cat_order_index ON fsb2_smilies_cat (cat_order);
DROP TABLE IF EXISTS fsb2_sub_procedure;
CREATE SEQUENCE fsb2_sub_procedure_seq;
CREATE TABLE fsb2_sub_procedure (
procedure_id INT DEFAULT nextval('fsb2_sub_procedure_seq'),
  procedure_name varchar(255),
  procedure_source text,
  procedure_auth INT2 default '2',
  PRIMARY KEY (procedure_id) 
);
DROP TABLE IF EXISTS fsb2_topics;
CREATE SEQUENCE fsb2_topics_seq;
CREATE TABLE fsb2_topics (
t_id INT DEFAULT nextval('fsb2_topics_seq'),
  f_id INT4 default '0',
  u_id INT4 default '0',
  t_title varchar(120) default '',
  t_total_view INT4 default '0',
  t_total_post INT4 default '0',
  t_time INT4 default '0',
  t_first_p_id INT4 default '0',
  t_last_p_id INT4 default '0',
  t_last_p_time INT4 default '0',
  t_last_u_id INT4 default '0',
  t_last_p_nickname varchar(255) default '',
  t_type INT2 default '0',
  t_status INT2 default '1',
  t_map varchar(255) default '',
  t_trace INT4 default '0',
  t_poll INT2 default '0',
  t_map_first_post INT2 default '0',
  t_description varchar(255) default '',
  t_approve INT2 default '0',
  PRIMARY KEY (t_id)
);
CREATE INDEX fsb2_topics_f_id_index ON fsb2_topics (f_id);
CREATE INDEX fsb2_topics_t_last_p_time_index ON fsb2_topics (t_last_p_time);
DROP TABLE IF EXISTS fsb2_topics_notification;
CREATE TABLE fsb2_topics_notification (
  u_id INT4 NOT NULL default '0',
  t_id INT4 NOT NULL default '0',
  tn_status INT2 NOT NULL default '0',
  PRIMARY KEY (u_id,t_id)
);
DROP TABLE IF EXISTS fsb2_topics_read;
CREATE TABLE fsb2_topics_read (
  u_id INT4 NOT NULL default '0',
  t_id INT4 NOT NULL default '0',
  p_id INT4 NOT NULL default '0',
  tr_last_time INT4 NOT NULL default '0',
  PRIMARY KEY (u_id,t_id)
);
DROP TABLE IF EXISTS fsb2_upload;
CREATE SEQUENCE fsb2_upload_seq;
CREATE TABLE fsb2_upload (
upload_id INT DEFAULT nextval('fsb2_upload_seq'),
  u_id INT4 default '0',
  upload_filename varchar(255) default '',
  upload_realname varchar(255) default '',
  upload_mimetype varchar(255) default '',
  upload_filesize INT4 default '0',
  upload_time INT4 default '0',
  upload_total INT4 default '0',
  upload_auth INT2 default '0',
  PRIMARY KEY (upload_id)
);
CREATE INDEX fsb2_upload_u_id_index ON fsb2_upload (u_id);
DROP TABLE IF EXISTS fsb2_users;
CREATE SEQUENCE fsb2_users_seq;
CREATE TABLE fsb2_users (
u_id INT DEFAULT nextval('fsb2_users_seq'),
  u_auth INT2 default '0',
  u_nickname varchar(40) default '',
  u_email varchar(255) default '',
  u_avatar varchar(255) default '',
  u_signature text,
  u_birthday char(10) default '0000/00/00',
  u_language varchar(255) default '',
  u_tpl varchar(255) default '',
  u_joined INT4 default '0',
  u_last_visit INT4 default '0',
  u_last_read INT4 default '0',
  u_last_read_flag INT2 default '0',
  u_total_post INT4 default '0',
  u_total_topic INT4 default '0',
  u_total_abuse INT4 default '-1',
  u_total_unapproved INT4 default '-1',
  u_total_mp INT4 default '0',
  u_avatar_method INT2 default '0',
  u_can_use_avatar INT2 default '1',
  u_can_use_sig INT2 default '1',
  u_sexe INT2 default '0',
  u_activate_redirection INT2 default '8',
  u_activate_email INT2 default '4',
  u_activate_auto_notification INT2 default '2',
  u_activate_mp_notification INT2 default '1',
  u_activate_hidden INT2 default '0',
  u_activate_fscode INT2 default '6',
  u_activate_avatar INT2 default '1',
  u_activate_sig INT2 default '1',
  u_activate_img INT2 default '6',
  u_activate_ajax INT2 default '1',
  u_activate_userlist INT2 default '1',
  u_mp_auto_answer_activ INT2 default '0',
  u_mp_auto_answer_message varchar(255) default '',
  u_rank_id INT4 default '0',
  u_activate_wysiwyg INT2 default '1',
  u_new_mp INT2 default '0',
  u_newsletter INT2 default '0',
  u_single_group_id INT4 default '0',
  u_default_group_id INT4 default '2',
  u_color varchar(255) default '',
  u_comment text,
  u_register_ip varchar(15),
  u_activated INT2,
  u_confirm_hash char(32) default '',
  u_total_warning INT2 default '0',
  u_warn_post INT4 default '0',
  u_warn_read INT4 default '0',
  u_utc INT2 default '0',
  u_utc_dst INT2,
  u_approve INT2 default '0',
  u_flood_post INT4 default '0',
  u_notepad text,
  PRIMARY KEY (u_id)
);
CREATE INDEX fsb2_users_u_nickname_index ON fsb2_users (u_nickname);
CREATE INDEX fsb2_users_u_birthday_index ON fsb2_users (u_birthday);
DROP TABLE IF EXISTS fsb2_users_password;
CREATE SEQUENCE fsb2_users_password_seq;
CREATE TABLE fsb2_users_password (
u_id INT DEFAULT nextval('fsb2_users_password_seq'),
  u_login varchar(255) default '',
  u_password varchar(255) default '',
  u_autologin_key varchar(40) default '',
  u_algorithm varchar(255) default '',
  u_use_salt INT2 default '1',
  PRIMARY KEY (u_id)
);
CREATE INDEX fsb2_users_password_u_login_index ON fsb2_users_password (u_login);
CREATE UNIQUE INDEX fsb2_users_password_u_autologin_key_index ON fsb2_users_password (u_autologin_key);
DROP TABLE IF EXISTS fsb2_users_contact;
CREATE TABLE fsb2_users_contact (
  u_id INT4 NOT NULL default '0',
  PRIMARY KEY (u_id)
);
DROP TABLE IF EXISTS fsb2_users_personal;
CREATE TABLE fsb2_users_personal (
  u_id INT4 NOT NULL default '0',
  PRIMARY KEY (u_id)
);
DROP TABLE IF EXISTS fsb2_warn;
CREATE SEQUENCE fsb2_warn_seq;
CREATE TABLE fsb2_warn (
warn_id INT DEFAULT nextval('fsb2_warn_seq'),
  u_id INT4,
  modo_id INT4,
  warn_type INT2,
  warn_reason text,
  warn_time INT4,
  warn_restriction_post varchar(255),
  warn_restriction_read varchar(255),
  PRIMARY KEY (warn_id)
);
CREATE INDEX fsb2_warn_u_id_index ON fsb2_warn (u_id);
CREATE INDEX fsb2_warn_warn_time_index ON fsb2_warn (warn_time);

