<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TranslatedAttributesSeeder extends Seeder
{
    // Common translations that are reused multiple times
    private array $commonTranslations = [
        'clear' => ['en' => 'Clear', 'fr' => 'Acquitter', 'de' => 'Erfüllen', 'zh' => '清除'],
        'delete' => ['en' => 'Delete', 'fr' => 'Supprimer', 'de' => 'Löschen', 'zh' => '删除'],
        'examination' => ['en' => 'Examination', 'fr' => 'Examen', 'de' => 'Prüfung', 'zh' => '审查'],
        'examReport' => ['en' => 'Exam Report', 'fr' => 'Rapport d\'Examen', 'de' => 'Prüfungsbericht', 'zh' => '审查报告'],
        'grantFee' => ['en' => 'Grant Fee', 'fr' => 'Taxe de Délivrance', 'de' => 'Erteilungsgebühr', 'zh' => '授权费'],
        'observations' => ['en' => 'Observations', 'fr' => 'Observations', 'de' => 'Anmerkungen', 'zh' => '意见'],
        'written' => ['en' => 'Written Opinion', 'fr' => 'Opinion Écrite', 'de' => 'Schriftlicher Bescheid', 'zh' => '书面意见'],
        'appeal' => ['en' => 'Appeal Brief', 'fr' => 'Mémoire de Recours', 'de' => 'Beschwerdebegründung', 'zh' => '上诉简报'],
        'declaration' => ['en' => 'Decl. and Assignment', 'fr' => 'Décl. et Cession', 'de' => 'Erklärung u. Abtretung', 'zh' => '声明和转让'],
        'opposition' => ['en' => 'Opposition deadline', 'fr' => 'Délai d\'Opposition', 'de' => 'Widerspruchsfrist', 'zh' => '异议期限'],
        'recurring' => ['en' => 'Recurring', 'fr' => 'Récurrent', 'de' => 'Wiederkehrend', 'zh' => '定期'],
        'extension' => ['en' => 'Request extension', 'fr' => 'Demander prolongation', 'de' => 'Verlängerung beantragen', 'zh' => '请求延期'],
    ];

    // Values that are standardized across languages (numbers, codes, acronyms)
    private array $standardizedValues = [
        'r71_3' => ['en' => 'R71(3)', 'zh' => 'R71(3)条款'],
        'r70_2' => ['en' => 'R70(2)', 'zh' => 'R70(2)条款'],
        'r161' => ['en' => 'R161', 'zh' => 'R161条款'],
        'rce' => ['en' => 'RCE', 'zh' => '继续审查请求'],
        'ids' => ['en' => 'IDS', 'zh' => '信息披露声明'],
        'poa' => ['en' => 'POA', 'zh' => '委托书'],
    ];

    /**
     * Run the database seeds.
     *
     * Populates the JSON columns with English (from original seeders),
     * French (machine-translated), and German (machine-translated) values.
     *
     * @return void
     */
    public function run()
    {
        Log::info('Starting TranslatedAttributesSeeder...');

        // Seed tables in order to satisfy foreign key constraints
        // event_name must be seeded before task_rules due to FK constraint

        // --- event_name.name ---
        // Based on EventNameTableSeeder.php
        $eventNames = [
            'ABA' => ['en' => 'Abandoned',           'fr' => 'Abandonné',                'de' => 'Aufgegeben', 'zh' => '放弃'],
            'ABO' => ['en' => 'Abandon Original',    'fr' => 'Abandon original',         'de' => 'Ursprüngliches aufgeben', 'zh' => '放弃原案'],
            'ADV' => ['en' => 'Advisory Action',     'fr' => 'Advisory Action',          'de' => 'Advisory Action', 'zh' => '咨询意见'],
            'ALL' => ['en' => 'Allowance',           'fr' => 'Intention délivrance',     'de' => 'Zulassung', 'zh' => '授权意向'],
            'APL' => ['en' => 'Appeal',              'fr' => 'Recours',                  'de' => 'Beschwerde', 'zh' => '上诉'],
            'CAN' => ['en' => 'Cancelled',           'fr' => 'Annulé',                   'de' => 'Storniert', 'zh' => '取消'],
            'CLO' => ['en' => 'Closed',              'fr' => 'Clôturé',                  'de' => 'Geschlossen', 'zh' => '关闭'],
            'COM' => ['en' => 'Communication',       'fr' => 'Communication',            'de' => 'Mitteilung', 'zh' => '通知'],
            'CRE' => ['en' => 'Created',             'fr' => 'Création',                 'de' => 'Erstellt', 'zh' => '创建'],
            'DAPL' => ['en' => 'Decision on Appeal',  'fr' => 'Décision sur recours',     'de' => 'Beschwerdeentscheidung', 'zh' => '上诉决定'],
            'DBY' => ['en' => 'Draft By',            'fr' => 'Rédiger avant le',         'de' => 'Entwurf bis', 'zh' => '起草截止'],
            'DEX' => ['en' => 'Deadline Extended',   'fr' => 'Délai prolongé',           'de' => 'Frist verlängert', 'zh' => '期限延长'],
            'DPAPL' => ['en' => 'Decision on Pre-Appeal', 'fr' => 'Décision pré-recours',  'de' => 'Vorentscheidung', 'zh' => '预上诉决定'],
            'DRA' => ['en' => 'Drafted',             'fr' => 'Rédigé',                   'de' => 'Entworfen', 'zh' => '已起草'],
            'DW' => ['en' => 'Deemed withrawn',     'fr' => 'Réputé retiré',            'de' => 'Als zurückgenommen geltend', 'zh' => '视为撤回'],
            'EHK' => ['en' => 'Extend to Hong Kong', 'fr' => 'Étendre à Hong Kong',      'de' => 'Erstreckung auf Hongkong', 'zh' => '延伸至香港'],
            'ENT' => ['en' => 'Entered',             'fr' => 'Entrée',                   'de' => 'Eingetreten', 'zh' => '进入'],
            'EOP' => ['en' => 'End of Procedure',    'fr' => 'Fin de procédure',         'de' => 'Verfahrensende', 'zh' => '程序结束'],
            'EXA' => ['en' => 'Examiner Action',     'fr' => 'Notification d\'examen',   'de' => 'Prüferbescheid', 'zh' => '审查员意见'],
            'EXAF' => ['en' => 'Examiner Action (Final)', 'fr' => 'Notif. Exa. (Finale)', 'de' => 'Schlussbescheid Prüfer', 'zh' => '审查员最终意见'],
            'EXP' => ['en' => 'Expiry',              'fr' => 'Expiration',               'de' => 'Ablauf', 'zh' => '到期'],
            'FAP' => ['en' => 'File Notice of Appeal', 'fr' => 'Déposer avis de recours', 'de' => 'Beschwerde einlegen', 'zh' => '提交上诉通知'],
            'FBY' => ['en' => 'File by',             'fr' => 'Déposer avant le',         'de' => 'Einreichen bis', 'zh' => '提交截止'],
            'FDIV' => ['en' => 'File Divisional',     'fr' => 'Déposer divisionnaire',    'de' => 'Teilanmeldung einreichen', 'zh' => '提交分案申请'],
            'FIL' => ['en' => 'Filed',               'fr' => 'Déposé',                   'de' => 'Eingereicht', 'zh' => '已提交'],
            'FOP' => ['en' => 'File Opposition',     'fr' => 'Déposer opposition',       'de' => 'Einspruch einlegen', 'zh' => '提交异议'],
            'FPR' => ['en' => 'Further Processing',  'fr' => 'Poursuite procédure',      'de' => 'Weiterbehandlung', 'zh' => '继续处理'],
            'FRCE' => ['en' => 'File RCE',            'fr' => 'Déposer RCE',              'de' => 'RCE einreichen', 'zh' => '提交RCE'],
            'GRT' => ['en' => 'Granted',             'fr' => 'Délivré',                  'de' => 'Erteilt', 'zh' => '已授权'],
            'INV' => ['en' => 'Invalidated',         'fr' => 'Invalidé',                 'de' => 'Für ungültig erklärt', 'zh' => '已无效'],
            'LAP' => ['en' => 'Lapsed',              'fr' => 'Déchu',                    'de' => 'Verfallen', 'zh' => '已失效'],
            'NPH' => ['en' => 'National Phase',      'fr' => 'Phase nationale',          'de' => 'Nationale Phase', 'zh' => '国家阶段'],
            'OPP' => ['en' => 'Opposition',          'fr' => 'Opposition',               'de' => 'Einspruch', 'zh' => '异议'],
            'OPR' => ['en' => 'Oral Proceedings',    'fr' => 'Procédure orale',          'de' => 'Mündliche Verhandlung', 'zh' => '口头审理'],
            'ORE' => ['en' => 'Opposition rejected', 'fr' => 'Opposition rejetée',       'de' => 'Einspruch zurückgewiesen', 'zh' => '异议驳回'],
            'PAY' => ['en' => 'Pay',                 'fr' => 'Payer',                    'de' => 'Zahlen', 'zh' => '支付'],
            'PDES' => ['en' => 'Post designation',    'fr' => 'Désignation postérieure',  'de' => 'Nachträgliche Benennung', 'zh' => '后续指定'],
            'PFIL' => ['en' => 'Parent Filed',        'fr' => 'Dépôt parent',             'de' => 'Stammanmeldung eingereicht', 'zh' => '母案已提交'],
            'PR' => ['en' => 'Publication of Reg.', 'fr' => 'Publication enreg.',       'de' => 'Veröffentlichung Reg.', 'zh' => '注册公告'],
            'PREP' => ['en' => 'Prepare',             'fr' => 'Préparer',                 'de' => 'Vorbereiten', 'zh' => '准备'],
            'PRI' => ['en' => 'Priority Claim',      'fr' => 'Revendication priorité',   'de' => 'Prioritätsanspruch', 'zh' => '优先权主张'],
            'PRID' => ['en' => 'Priority Deadline',   'fr' => 'Délai priorité',           'de' => 'Prioritätsfrist', 'zh' => '优先权期限'],
            'PROD' => ['en' => 'Produce',             'fr' => 'Produire',                 'de' => 'Erstellen/Einreichen', 'zh' => '制作'],
            'PSR' => ['en' => 'Publication of SR',   'fr' => 'Publication rap. rech.',   'de' => 'Veröffentlichung Rech.ber.', 'zh' => '检索报告公布'],
            'PUB' => ['en' => 'Published',           'fr' => 'Publié',                   'de' => 'Veröffentlicht', 'zh' => '已公布'],
            'RCE' => ['en' => 'Request Continued Examination', 'fr' => 'Requête RCE',    'de' => 'Antrag auf Fortsetzung Prüfung', 'zh' => '请求继续审查'],
            'REC' => ['en' => 'Received',            'fr' => 'Reçu',                     'de' => 'Empfangen', 'zh' => '已收到'],
            'REF' => ['en' => 'Refused',             'fr' => 'Refusé',                   'de' => 'Zurückgewiesen', 'zh' => '驳回'],
            'REG' => ['en' => 'Registration',        'fr' => 'Enregistrement',           'de' => 'Registrierung', 'zh' => '注册'],
            'REM' => ['en' => 'Reminder',            'fr' => 'Rappel',                   'de' => 'Erinnerung', 'zh' => '提醒'],
            'REN' => ['en' => 'Renewal',             'fr' => 'Renouvellement',           'de' => 'Verlängerung', 'zh' => '续展'],
            'REP' => ['en' => 'Respond',             'fr' => 'Répondre',                 'de' => 'Erwidern', 'zh' => '答复'],
            'REQ' => ['en' => 'Request',             'fr' => 'Requête',                  'de' => 'Antrag', 'zh' => '请求'],
            'RSTR' => ['en' => 'Restriction Req.',    'fr' => 'Requête restriction',      'de' => 'Beschränkungsantrag', 'zh' => '限制要求'],
            'SOL' => ['en' => 'Sold',                'fr' => 'Vendu',                    'de' => 'Verkauft', 'zh' => '已出售'],
            'SOP' => ['en' => 'Summons to Oral Proc.', 'fr' => 'Citation proc. orale',   'de' => 'Ladung zur Mündl. Verh.', 'zh' => '口头审理传唤'],
            'SR' => ['en' => 'Search Report',       'fr' => 'Rapport de recherche',     'de' => 'Recherchenbericht', 'zh' => '检索报告'],
            'SUS' => ['en' => 'Suspended',           'fr' => 'Suspendu',                 'de' => 'Ausgesetzt', 'zh' => '暂停'],
            'TRF' => ['en' => 'Transformation',      'fr' => 'Transformation',           'de' => 'Transformation', 'zh' => '转化'],
            'TRS' => ['en' => 'Transfer',            'fr' => 'Transfert',                'de' => 'Übertragung', 'zh' => '转让'],
            'VAL' => ['en' => 'Validate',            'fr' => 'Valider',                  'de' => 'Validieren', 'zh' => '生效'],
            'WAT' => ['en' => 'Watch',               'fr' => 'Surveiller',               'de' => 'Überwachen', 'zh' => '监视'],
            'WIT' => ['en' => 'Withdrawal',          'fr' => 'Retrait',                  'de' => 'Zurücknahme', 'zh' => '撤回'],
        ];
        $this->updateTable('event_name', 'code', 'name', $eventNames);

        // --- matter_category.category ---
        // Based on MatterCategoryTableSeeder.php
        $matterCategories = [
            'AGR' => ['en' => 'Agreement',           'fr' => 'Accord',              'de' => 'Vereinbarung', 'zh' => '协议'],
            'DSG' => ['en' => 'Design',              'fr' => 'Dessin ou modèle',    'de' => 'Design', 'zh' => '外观设计'],
            'FTO' => ['en' => 'Freedom to Operate',  'fr' => 'Liberté d\'exploitation', 'de' => 'Freedom to Operate', 'zh' => '自由实施'],
            'LTG' => ['en' => 'Litigation',          'fr' => 'Contentieux',         'de' => 'Rechtsstreit', 'zh' => '诉讼'],
            'OP' => ['en' => 'Opposition (patent)', 'fr' => 'Opposition (brevet)', 'de' => 'Einspruch (Patent)', 'zh' => '异议（专利）'],
            'OPI' => ['en' => 'Opinion',             'fr' => 'Avis',                'de' => 'Gutachten', 'zh' => '意见书'], // Or 'Meinung'? Context matters.
            'OTH' => ['en' => 'Others',              'fr' => 'Autres',              'de' => 'Sonstige', 'zh' => '其他'],
            'PAT' => ['en' => 'Patent',              'fr' => 'Brevet',              'de' => 'Patent', 'zh' => '专利'],
            'SO' => ['en' => 'Soleau Envelop',      'fr' => 'Enveloppe Soleau',    'de' => 'Soleau-Umschlag', 'zh' => 'Soleau信封'],
            'SR' => ['en' => 'Search',              'fr' => 'Recherche',           'de' => 'Recherche', 'zh' => '检索'],
            'TM' => ['en' => 'Trademark',           'fr' => 'Marque',              'de' => 'Marke', 'zh' => '商标'],
            'TMOP' => ['en' => 'Opposition (TM)',     'fr' => 'Opposition (Marque)', 'de' => 'Widerspruch (Marke)', 'zh' => '异议（商标）'],
            'TS' => ['en' => 'Trade Secret',        'fr' => 'Secret de fabrique',  'de' => 'Geschäftsgeheimnis', 'zh' => '商业秘密'],
            'UC' => ['en' => 'Utility Certificate', 'fr' => 'Certificat d\'utilité', 'de' => 'Gebrauchszertifikat', 'zh' => '实用证书'],
            'UM' => ['en' => 'Utility Model',       'fr' => 'Modèle d\'utilité',   'de' => 'Gebrauchsmuster', 'zh' => '实用新型'],
            'WAT' => ['en' => 'Watch',               'fr' => 'Surveillance',        'de' => 'Überwachung', 'zh' => '监视'],
        ];
        $this->updateTable('matter_category', 'code', 'category', $matterCategories);

        // --- matter_type.type ---
        // Based on MatterTypeTableSeeder.php
        $matterTypes = [
            'CIP' => ['en' => 'Continuation in Part', 'fr' => 'Continuation partielle', 'de' => 'Teilfortsetzungsanmeldung', 'zh' => '部分延续案'],
            'CNT' => ['en' => 'Continuation',         'fr' => 'Continuation',           'de' => 'Fortsetzungsanmeldung', 'zh' => '延续案'],
            'DIV' => ['en' => 'Divisional',           'fr' => 'Divisionnaire',          'de' => 'Teilanmeldung', 'zh' => '分案'],
            'PRO' => ['en' => 'Provisional',          'fr' => 'Provisoire',             'de' => 'Vorläufige Anmeldung', 'zh' => '临时申请'],
            'REI' => ['en' => 'Reissue',              'fr' => 'Redélivrance',           'de' => 'Neuerteilung', 'zh' => '再颁发'],
            'REX' => ['en' => 'Re-examination',       'fr' => 'Réexamen',               'de' => 'Neuprüfungsverfahren', 'zh' => '复审'],
        ];
        $this->updateTable('matter_type', 'code', 'type', $matterTypes);

        // --- actor_role.name ---
        // Based on ActorRoleTableSeeder.php
        $actorRoles = [
            'ADV' => ['en' => 'Adversary',        'fr' => 'Adversaire',           'de' => 'Gegenpartei', 'zh' => '对手'],
            'AGT' => ['en' => 'Primary Agent',    'fr' => 'Agent principal',      'de' => 'Hauptvertreter', 'zh' => '主代理人'],
            'AGT2' => ['en' => 'Secondary Agent',  'fr' => 'Agent secondaire',     'de' => 'Zweitvertreter', 'zh' => '副代理人'],
            'ANN' => ['en' => 'Annuity Agent',    'fr' => 'Agent annuités',       'de' => 'Jahresgebührenvertreter', 'zh' => '年费代理人'],
            'APP' => ['en' => 'Applicant',        'fr' => 'Déposant',             'de' => 'Anmelder', 'zh' => '申请人'],
            'CLI' => ['en' => 'Client',           'fr' => 'Client',               'de' => 'Mandant', 'zh' => '客户'],
            'CNT' => ['en' => 'Contact',          'fr' => 'Contact',              'de' => 'Kontakt', 'zh' => '联系人'],
            'DBA' => ['en' => 'DB Administrator', 'fr' => 'BDD Admin.',           'de' => 'DB-Administrator', 'zh' => '数据库管理员'],
            'DBRO' => ['en' => 'DB Read-Only',     'fr' => 'BDD Lecture seule',    'de' => 'DB Nur-Lesezugriff', 'zh' => '数据库只读'],
            'DBRW' => ['en' => 'DB Read/Write',    'fr' => 'BDD Lecture/écriture', 'de' => 'DB Lese-/Schreibzugriff', 'zh' => '数据库读写'],
            'DEL' => ['en' => 'Delegate',         'fr' => 'Délégataire',          'de' => 'Bevollmächtigter', 'zh' => '代表'],
            'FAGT' => ['en' => 'Former Agent',     'fr' => 'Ancien agent',         'de' => 'Ehemaliger Vertreter', 'zh' => '前任代理人'],
            'FOWN' => ['en' => 'Former Owner',     'fr' => 'Ancien titulairte',    'de' => 'Ehemaliger Inhaber', 'zh' => '前任所有人'],
            'INV' => ['en' => 'Inventor',         'fr' => 'Inventeur',            'de' => 'Erfinder', 'zh' => '发明人'],
            'LCN' => ['en' => 'Licensee',         'fr' => 'Licencié',             'de' => 'Lizenznehmer', 'zh' => '被许可人'],
            'OFF' => ['en' => 'Patent Office',    'fr' => 'Office des brevets',   'de' => 'Patentamt', 'zh' => '专利局'],
            'OPP' => ['en' => 'Opponent',         'fr' => 'Opposant',             'de' => 'Einsprechender', 'zh' => '异议人'],
            'OWN' => ['en' => 'Owner',            'fr' => 'Titulaire',            'de' => 'Inhaber', 'zh' => '所有人'],
            'PAY' => ['en' => 'Payor',            'fr' => 'Payeur',               'de' => 'Zahler', 'zh' => '付款人'],
            'PTNR' => ['en' => 'Partner',          'fr' => 'Partenaire',           'de' => 'Partner', 'zh' => '合伙人'],
            'TRA' => ['en' => 'Translator',       'fr' => 'Traducteur',           'de' => 'Übersetzer', 'zh' => '翻译'],
            'WRI' => ['en' => 'Writer',           'fr' => 'Rédacteur',            'de' => 'Verfasser', 'zh' => '撰稿人'],
        ];
        $this->updateTable('actor_role', 'code', 'name', $actorRoles);

        // --- classifier_type.type ---
        // Based on ClassifierTypeTableSeeder.php
        $classifierTypes = [
            'ABS' => ['en' => 'Abstract',         'fr' => 'Abrégé',           'de' => 'Zusammenfassung', 'zh' => '摘要'],
            'AGR' => ['en' => 'Agreement',        'fr' => 'Accord',           'de' => 'Vereinbarung', 'zh' => '协议'],
            'BU' => ['en' => 'Business Unit',    'fr' => 'Unité commerciale', 'de' => 'Geschäftsbereich', 'zh' => '业务单元'],
            'DESC' => ['en' => 'Description',      'fr' => 'Description',      'de' => 'Beschreibung', 'zh' => '说明'],
            'EVAL' => ['en' => 'Evaluation',       'fr' => 'Évaluation',       'de' => 'Bewertung', 'zh' => '评估'],
            'IMG' => ['en' => 'Image',            'fr' => 'Image',            'de' => 'Bild', 'zh' => '图片'],
            'IPC' => ['en' => 'Int. Pat. Class.', 'fr' => 'Class. Int. des Brevets', 'de' => 'Int. Pat. Klass.', 'zh' => '国际专利分类'],
            'KW' => ['en' => 'Keyword',          'fr' => 'Mot-clé',          'de' => 'Stichwort', 'zh' => '关键词'],
            'LNK' => ['en' => 'Link',             'fr' => 'Lien',             'de' => 'Link', 'zh' => '链接'],
            'LOC' => ['en' => 'Location',         'fr' => 'Lieu',             'de' => 'Standort', 'zh' => '位置'],
            'ORG' => ['en' => 'Organization',     'fr' => 'Organisation',     'de' => 'Organisation', 'zh' => '组织'],
            'PA' => ['en' => 'Prior Art',        'fr' => 'Art antérieur',    'de' => 'Stand der Technik', 'zh' => '现有技术'],
            'PROD' => ['en' => 'Product',          'fr' => 'Produit',          'de' => 'Produkt', 'zh' => '产品'],
            'PROJ' => ['en' => 'Project',          'fr' => 'Projet',           'de' => 'Projekt', 'zh' => '项目'],
            'TECH' => ['en' => 'Technology',       'fr' => 'Technologie',      'de' => 'Technologie', 'zh' => '技术'],
            'TIT' => ['en' => 'Title',            'fr' => 'Titre',            'de' => 'Titel', 'zh' => '标题'],
            'TITAL' => ['en' => 'Alt. Title',       'fr' => 'Titre alternatif', 'de' => 'Alternativer Titel', 'zh' => '替代标题'],
            'TITEN' => ['en' => 'English Title',    'fr' => 'Titre anglais',    'de' => 'Englischer Titel', 'zh' => '英文标题'],
            'TITOF' => ['en' => 'Official Title',   'fr' => 'Titre officiel',   'de' => 'Offizieller Titel', 'zh' => '正式标题'],
            'TM' => ['en' => 'Trademark',        'fr' => 'Marque',           'de' => 'Marke', 'zh' => '商标'],
            'TMCL' => ['en' => 'Class (TM)',       'fr' => 'Classe (Marque)',  'de' => 'Klasse (Marke)', 'zh' => '分类（商标）'],
            'TMTYP' => ['en' => 'Type (TM)',        'fr' => 'Type (Marque)',    'de' => 'Typ (Marke)', 'zh' => '类型（商标）'],
        ];
        $this->updateTable('classifier_type', 'code', 'type', $classifierTypes);

        // --- task_rules.detail ---
        // Based on TaskRulesTableSeeder.php
        // !! WARNING !! MAPPING USES PRIMARY KEY `id`.
        $taskRuleDetails = [
            3 => $this->commonTranslations['clear'],
            5 => $this->commonTranslations['clear'],
            6 => $this->commonTranslations['examination'],
            7 => $this->commonTranslations['examination'],
            9 => ['en' => 'Search Report', 'fr' => 'Rapport de Recherche', 'de' => 'Recherchenbericht', 'zh' => '检索报告'],
            10 => $this->commonTranslations['examReport'],
            11 => $this->commonTranslations['examReport'],
            13 => $this->standardizedValues['r71_3'],
            14 => $this->commonTranslations['grantFee'],
            15 => ['en' => 'Claim Translation', 'fr' => 'Traduction Revendications', 'de' => 'Anspruchsübersetzung', 'zh' => '权利要求翻译'],
            16 => ['en' => 'Translate where necessary', 'fr' => 'Traduire si nécessaire', 'de' => 'Übersetzen wo nötig', 'zh' => '必要时翻译'],
            18 => $this->commonTranslations['written'],
            19 => ['en' => 'Designation Fees', 'fr' => 'Taxes de Désignation', 'de' => 'Benennungsgebühren', 'zh' => '指定费'],
            20 => $this->commonTranslations['declaration'],
            21 => ['en' => 'Priority Deadline', 'fr' => 'Délai de Priorité', 'de' => 'Prioritätsfrist', 'zh' => '优先权期限'],
            23 => $this->commonTranslations['examination'],
            25 => $this->commonTranslations['delete'],
            30 => $this->standardizedValues['ids'],
            34 => ['en' => 'National Phase', 'fr' => 'Phase Nationale', 'de' => 'Nationale Phase', 'zh' => '国家阶段'],
            35 => ['en' => 'Small Entity', 'fr' => 'Petite Entité', 'de' => 'Kleines Unternehmen', 'zh' => '小实体'],
            36 => ['en' => 'HK Grant Fee', 'fr' => 'Taxe Délivrance HK', 'de' => 'HK Erteilungsgebühr', 'zh' => '香港授权费'],
            37 => ['en' => 'Communication', 'fr' => 'Communication', 'de' => 'Mitteilung', 'zh' => '通知'],
            38 => $this->commonTranslations['clear'],
            39 => $this->commonTranslations['grantFee'],
            41 => $this->standardizedValues['r70_2'],
            44 => ['en' => 'Filing Fee', 'fr' => 'Taxe de Dépôt', 'de' => 'Anmeldegebühr', 'zh' => '申请费'],
            46 => ['en' => 'Restriction Req.', 'fr' => 'Requête Restriction', 'de' => 'Beschränkungsantrag', 'zh' => '限制要求'],
            47 => $this->standardizedValues['r161'],
            49 => $this->commonTranslations['appeal'],
            52 => $this->commonTranslations['observations'],
            53 => $this->commonTranslations['examination'],
            54 => $this->commonTranslations['examination'],
            55 => $this->commonTranslations['examination'],
            56 => $this->commonTranslations['grantFee'],
            57 => ['en' => 'Priority Docs', 'fr' => 'Documents Priorité', 'de' => 'Prioritätsunterlagen', 'zh' => '优先权文件'],
            58 => ['en' => 'Filing Fee', 'fr' => 'Taxe de Dépôt', 'de' => 'Anmeldegebühr', 'zh' => '申请费'],
            60 => ['en' => 'File divisional', 'fr' => 'Déposer divisionnaire', 'de' => 'Teilanmeldung einreichen', 'zh' => '提交分案申请'],
            61 => $this->commonTranslations['examReport'],
            62 => $this->commonTranslations['examReport'],
            63 => $this->commonTranslations['extension'],
            64 => $this->commonTranslations['extension'],
            66 => $this->commonTranslations['grantFee'],
            67 => $this->standardizedValues['r70_2'],
            68 => ['en' => 'Designation Fees', 'fr' => 'Taxes de Désignation', 'de' => 'Benennungsgebühren', 'zh' => '指定费'],
            69 => $this->commonTranslations['written'],
            70 => $this->commonTranslations['examination'],
            80 => $this->commonTranslations['recurring'],
            81 => $this->commonTranslations['recurring'],
            234 => $this->commonTranslations['grantFee'],
            235 => $this->commonTranslations['written'],
            236 => $this->commonTranslations['grantFee'],
            237 => ['en' => 'Working Report', 'fr' => 'Rapport d\'Exploitation', 'de' => 'Nutzungsbericht', 'zh' => '工作报告'],
            238 => $this->commonTranslations['opposition'],
            239 => $this->commonTranslations['opposition'],
            240 => $this->commonTranslations['opposition'],
            242 => ['en' => 'Declaration of use', 'fr' => 'Déclaration d\'Usage', 'de' => 'Benutzungserklärung', 'zh' => '使用声明'],
            1280 => $this->commonTranslations['observations'],
            1282 => ['en' => '2nd part of individual fee', 'fr' => '2ème partie taxe indiv.', 'de' => '2. Teil Individualgebühr', 'zh' => '第二部分个人费用'],
            1290 => ['en' => 'Soleau', 'zh' => 'Soleau信封'],
            1291 => ['en' => 'End of protection', 'fr' => 'Fin de protection', 'de' => 'Schutzende', 'zh' => '保护期限届满'],
            1300 => $this->commonTranslations['observations'],
            1301 => $this->commonTranslations['declaration'],
            1302 => $this->commonTranslations['examination'],
            1303 => $this->commonTranslations['appeal'],
            1306 => $this->commonTranslations['delete'],
            1307 => $this->commonTranslations['delete'],
            1310 => ['en' => 'Opinion', 'fr' => 'Avis', 'de' => 'Gutachten', 'zh' => '意见书'],
            1311 => ['en' => 'Report', 'fr' => 'Rapport', 'de' => 'Bericht', 'zh' => '报告'],
            1315 => $this->commonTranslations['examReport'],
            1316 => $this->standardizedValues['poa'],
            1321 => ['en' => 'Analysis of SR', 'fr' => 'Analyse du Rap. Rech.', 'de' => 'Analyse Rech.ber.', 'zh' => '检索报告分析'],
            1322 => $this->commonTranslations['appeal'],
            1323 => $this->standardizedValues['rce'],
            1326 => ['en' => 'Appeal', 'fr' => 'Recours', 'de' => 'Beschwerde', 'zh' => '上诉'],
            1327 => $this->commonTranslations['clear'],
            1328 => ['en' => 'CompuMark Analysis', 'fr' => 'Analyse CompuMark', 'de' => 'CompuMark Analyse', 'zh' => 'CompuMark分析'],
            1329 => ['en' => 'Products & Services', 'fr' => 'Produits & Services', 'de' => 'Produkte & Dienstleistungen', 'zh' => '商品和服务'],
        ];
        // Filter out any null detail values that might have slipped in, if desired
        $taskRuleDetails = array_filter($taskRuleDetails, fn ($detailArray) => isset($detailArray['en']) && $detailArray['en'] !== null);

        if (! empty($taskRuleDetails)) {
            $this->updateTable('task_rules', 'id', 'detail', $taskRuleDetails); // Uses 'id' as key
        } else {
            Log::info('No translatable details configured for task_rules. Skipping update for this table.');
        }

        Log::info('TranslatedAttributesSeeder finished.');
    }

    /**
     * Helper function to update a table with translated JSON data.
     * (Same helper function as before)
     *
     * @param  string  $keyColumn  (e.g., 'code' or 'id')
     * @param  string  $targetJsonColumn  (e.g., 'category', 'name', 'detail')
     * @param  array  $translationsData  [$keyValue => ['en' => ..., 'fr' => ..., 'de' => ...]]
     * @return void
     */
    private function updateTable(string $tableName, string $keyColumn, string $targetJsonColumn, array $translationsData)
    {
        if (empty($translationsData)) {
            Log::info("No data provided for {$tableName}. Skipping update.");

            return;
        }

        Log::info("Updating table '{$tableName}', column '{$targetJsonColumn}' using key '{$keyColumn}'...");
        $updatedCount = 0;
        $errorCount = 0;

        // Wrap per-table update in transaction for atomicity
        DB::transaction(function () use ($tableName, $keyColumn, $targetJsonColumn, $translationsData, &$updatedCount, &$errorCount) {
            foreach ($translationsData as $keyValue => $translations) {
                try {
                    // Ensure translations is an array and not empty (safeguard against incomplete entries)
                    if (! is_array($translations) || empty(array_filter($translations))) {
                        Log::warning("Skipping update for {$tableName}.{$keyColumn} = {$keyValue}: Invalid or empty translations array provided.");

                        continue;
                    }

                    // Ensure English translation exists if expected
                    if (! isset($translations['en'])) {
                        Log::warning("Skipping update for {$tableName}.{$keyColumn} = {$keyValue}: Missing 'en' key in translations array.");

                        continue;
                    }

                    $jsonPayload = json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT); // Pretty print for readability in DB

                    // Update existing records with translations
                    $count = DB::table($tableName)
                        ->where($keyColumn, $keyValue)
                        ->update([$targetJsonColumn => $jsonPayload]);

                    if ($count === 0 && DB::table($tableName)->where($keyColumn, $keyValue)->doesntExist()) {
                        Log::warning("Row not found in {$tableName} for {$keyColumn} = '{$keyValue}'. Could not update.");
                    }

                    if ($count > 0) {
                        $updatedCount += $count;
                    }

                } catch (\JsonException $e) {
                    Log::error("JSON encoding failed for {$tableName}.{$keyColumn} = '{$keyValue}': ".$e->getMessage());
                    $errorCount++;
                } catch (\Exception $e) {
                    Log::error("Database update failed for {$tableName}.{$keyColumn} = '{$keyValue}': ".$e->getMessage());
                    $errorCount++;
                    // Consider re-throwing to stop the whole transaction on DB errors:
                    // throw $e;
                }
            }
        });

        Log::info("Finished updating {$tableName}. Updated records: {$updatedCount}. Errors: {$errorCount}.");
    }
}
