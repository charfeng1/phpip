/*
/*
/*
/*
/*
/*
DROP TABLE IF EXISTS "actor";
/*
;


 ;
 ;
 ;
 ;
;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
;


 ;
 ;
 ;
 ;
;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
;


 ;
 ;
 ;
 ;
;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
 ;
;


 ;
 ;
 ;
 ;
;
;
;
;
;
;
;


;
;
;
;
;
;
;
;
;
;
;


;
;
;
;
;
;
;
;
;
;
;


;
;
;
;
;
;
;
;
;
;
;


;
;
;
;
;
;
;
;
;
;
;


;
;
;
;
;
;
;
;
;
;
;


;
;
;
;
/*

/*
/*
/*
/*

INSERT INTO "migrations" ("id", "migration", "batch") VALUES (1,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (2,'2018_12_07_184310_create_actor_role_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (3,'2018_12_07_184310_create_actor_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (4,'2018_12_07_184310_create_classifier_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (5,'2018_12_07_184310_create_classifier_type_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (6,'2018_12_07_184310_create_classifier_value_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (7,'2018_12_07_184310_create_country_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (8,'2018_12_07_184310_create_default_actor_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (9,'2018_12_07_184310_create_event_name_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (10,'2018_12_07_184310_create_event_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (11,'2018_12_07_184310_create_matter_actor_lnk_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (12,'2018_12_07_184310_create_matter_category_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (13,'2018_12_07_184310_create_matter_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (14,'2018_12_07_184310_create_matter_type_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (15,'2018_12_07_184310_create_task_rules_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (16,'2018_12_07_184310_create_task_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (17,'2018_12_07_184312_add_foreign_keys_to_actor_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (18,'2018_12_07_184312_add_foreign_keys_to_classifier_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (19,'2018_12_07_184312_add_foreign_keys_to_classifier_type_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (20,'2018_12_07_184312_add_foreign_keys_to_classifier_value_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (21,'2018_12_07_184312_add_foreign_keys_to_default_actor_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (22,'2018_12_07_184312_add_foreign_keys_to_event_name_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (23,'2018_12_07_184312_add_foreign_keys_to_event_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (24,'2018_12_07_184312_add_foreign_keys_to_matter_actor_lnk_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (25,'2018_12_07_184312_add_foreign_keys_to_matter_category_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (26,'2018_12_07_184312_add_foreign_keys_to_matter_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (27,'2018_12_07_184312_add_foreign_keys_to_task_rules_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (28,'2018_12_07_184312_add_foreign_keys_to_task_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (29,'2018_12_08_000109_add_trigger',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (30,'2018_12_08_002558_create_views_and_functions',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (31,'2019_03_07_171752_create_procedure_recalculate_tasks',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (32,'2019_03_07_171910_create_procedure_recreate_tasks',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (33,'2019_08_13_145446_update_tables',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (34,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (35,'2019_11_13_135330_update_tables2',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (36,'2019_11_17_025422_update_tables3',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (37,'2019_11_18_002207_update_tables4',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (38,'2019_11_25_123348_update_tables5',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (39,'2019_11_26_192706_create_user_view',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (40,'2019_12_06_000000_create_fees_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (41,'2019_12_06_002_alter_task_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (42,'2019_12_06_003_create_renewal_list_view',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (43,'2020_01_06_181200_update_tables6',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (44,'2020_01_21_173000_update_tables7',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (45,'2020_01_28_122217_update_db_roles',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (46,'2020_01_30_001_create_renewals_logs_table',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (47,'2020_02_02_105653_add_timestamps_default_actors',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (48,'2020_02_12_144400_update_procedure_update_expired',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (49,'2020_02_22_161215_create_template_classes',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (50,'2020_02_22_164446_create_template_members',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (51,'2020_02_22_173742_create_event_class_lnk',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (52,'2020_02_22_181558_add_foreignkeys_to_template_members',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (53,'2020_02_22_183512_add_foreignkeys_to_template_classes',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (54,'2020_02_24_110300_update_tables8c',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (55,'2020_02_24_190000_update_rules2',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (56,'2020_02_24_192100_implement_generic_renewals',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (57,'2020_03_23_110300_update_tables9c',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (58,'2020_03_28_190000_update_country',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (59,'2020_04_12_183512_add_foreignkeys_to_event_class_lnk',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (60,'2020_04_15_110300_update_tables10',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (61,'2020_06_16_122700_update_actor_lang',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (62,'2020_06_23_200200_remove_parent_filed_deps',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (63,'2020_07_29_190800_fix_insert_recurring_renewals',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (64,'2020_07_30_091000_update_special_countries',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (65,'2020_09_18_135000_update_tables11',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (66,'2020_10_01_130832_update_category_provisional',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (67,'2020_12_04_133640_update_matter_alt_ref',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (68,'2021_01_29_203100_update_procedure_recalculate_tasks',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (69,'2021_02_02_120309_update_classifier_uqvalue_index',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (70,'2021_05_18_174249_country_mx_renewals',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (71,'2021_08_16_163607_country_ma_renewals',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (72,'2021_13_03_165000_country_pl_renewals',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (73,'2023_02_16_113312_insert_new_country_codes',1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (74,'2023_05_22_092530_insert_unitary_patent',1);
