-- phpIP PostgreSQL Schema
-- Converted from MySQL schema for PostgreSQL/Supabase compatibility
--
-- Key differences from MySQL:
-- - No unsigned integers (PostgreSQL doesn't support them)
-- - BOOLEAN instead of TINYINT(1)
-- - BYTEA instead of MEDIUMBLOB
-- - Triggers require separate functions
-- - SERIAL/BIGSERIAL for auto-increment
-- - Different date/time functions

-- ============================================================================
-- EXTENSIONS
-- ============================================================================
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================================
-- TABLES
-- ============================================================================

-- Country table (must be created first due to foreign key dependencies)
DROP TABLE IF EXISTS country CASCADE;
CREATE TABLE country (
    numcode SMALLINT,
    iso CHAR(2) NOT NULL PRIMARY KEY,
    iso3 CHAR(3),
    name_DE VARCHAR(80),
    name JSONB NOT NULL DEFAULT '{}',
    name_FR VARCHAR(80),
    ep BOOLEAN DEFAULT FALSE,
    wo BOOLEAN DEFAULT FALSE,
    em BOOLEAN DEFAULT FALSE,
    oa BOOLEAN DEFAULT FALSE,
    renewal_first SMALLINT DEFAULT 2,
    renewal_base CHAR(5) DEFAULT 'FIL',
    renewal_start CHAR(5) DEFAULT 'FIL',
    checked_on DATE
);

COMMENT ON COLUMN country.ep IS 'Flag default countries for EP ratifications';
COMMENT ON COLUMN country.wo IS 'Flag default countries for PCT national phase';
COMMENT ON COLUMN country.em IS 'Flag default countries for EU trade mark';
COMMENT ON COLUMN country.oa IS 'Flag default countries for OA national phase';
COMMENT ON COLUMN country.renewal_first IS 'The first year a renewal is due in this country from renewal_base. When negative, the date is calculated from renewal_start';
COMMENT ON COLUMN country.renewal_base IS 'The base event for calculating renewal deadlines';
COMMENT ON COLUMN country.renewal_start IS 'The event from which renewals become due';

-- Actor role table
DROP TABLE IF EXISTS actor_role CASCADE;
CREATE TABLE actor_role (
    code CHAR(5) NOT NULL PRIMARY KEY,
    name JSONB NOT NULL DEFAULT '{}',
    display_order SMALLINT DEFAULT 127,
    shareable BOOLEAN NOT NULL DEFAULT FALSE,
    show_ref BOOLEAN NOT NULL DEFAULT FALSE,
    show_company BOOLEAN NOT NULL DEFAULT FALSE,
    show_rate BOOLEAN NOT NULL DEFAULT FALSE,
    show_date BOOLEAN NOT NULL DEFAULT FALSE,
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN actor_role.display_order IS 'Order of display in interface';
COMMENT ON COLUMN actor_role.shareable IS 'Indicates whether actors listed with this role are shareable for all matters of the same family';

CREATE INDEX idx_actor_role_name_en ON actor_role((name->>'en'));

-- Actor table
DROP TABLE IF EXISTS actor CASCADE;
CREATE TABLE actor (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    first_name VARCHAR(60),
    display_name VARCHAR(30) UNIQUE,
    login CHAR(16) UNIQUE,
    password VARCHAR(60),
    default_role CHAR(5) REFERENCES actor_role(code) ON DELETE SET NULL ON UPDATE CASCADE,
    function VARCHAR(45),
    parent_id INTEGER REFERENCES actor(id) ON DELETE SET NULL ON UPDATE CASCADE,
    company_id INTEGER REFERENCES actor(id) ON DELETE SET NULL ON UPDATE CASCADE,
    site_id INTEGER REFERENCES actor(id) ON DELETE SET NULL ON UPDATE CASCADE,
    phy_person BOOLEAN NOT NULL DEFAULT TRUE,
    nationality CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    language CHAR(2),
    small_entity BOOLEAN NOT NULL DEFAULT FALSE,
    address VARCHAR(256),
    country CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    address_mailing VARCHAR(256),
    country_mailing CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    address_billing VARCHAR(256),
    country_billing CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    email VARCHAR(45),
    phone VARCHAR(20),
    legal_form VARCHAR(60),
    registration_no VARCHAR(20),
    warn BOOLEAN NOT NULL DEFAULT FALSE,
    ren_discount DECIMAL(8,2) NOT NULL DEFAULT 0,
    notes TEXT,
    VAT_number VARCHAR(45),
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    remember_token VARCHAR(100)
);

COMMENT ON COLUMN actor.name IS 'Family name or company name';
COMMENT ON COLUMN actor.first_name IS 'plus middle names, if required';
COMMENT ON COLUMN actor.display_name IS 'The name displayed in the interface, if not null';
COMMENT ON COLUMN actor.login IS 'Database user login if not null';
COMMENT ON COLUMN actor.default_role IS 'Link to actor_role table. A same actor can have different roles - this is the default role of the actor.';
COMMENT ON COLUMN actor.parent_id IS 'Parent company of this company (another actor), where applicable';
COMMENT ON COLUMN actor.company_id IS 'Mainly for inventors and contacts. ID of the actor company or employer';
COMMENT ON COLUMN actor.site_id IS 'Mainly for inventors and contacts. ID of the actor company site';
COMMENT ON COLUMN actor.phy_person IS 'Physical person or not';
COMMENT ON COLUMN actor.small_entity IS 'Small entity status used in a few countries (FR, US)';
COMMENT ON COLUMN actor.address IS 'Main address: street, zip and city';
COMMENT ON COLUMN actor.warn IS 'The actor will be displayed in red in the matter view when set';

CREATE INDEX idx_actor_name ON actor(name);

-- Matter category table
DROP TABLE IF EXISTS matter_category CASCADE;
CREATE TABLE matter_category (
    code CHAR(5) NOT NULL PRIMARY KEY,
    category JSONB NOT NULL DEFAULT '{}',
    display_with VARCHAR(5),
    ref_prefix CHAR(5),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN matter_category.display_with IS 'Display with category code';

-- Matter type table
DROP TABLE IF EXISTS matter_type CASCADE;
CREATE TABLE matter_type (
    code CHAR(5) NOT NULL PRIMARY KEY,
    type JSONB NOT NULL DEFAULT '{}',
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Event name table
DROP TABLE IF EXISTS event_name CASCADE;
CREATE TABLE event_name (
    code CHAR(5) NOT NULL PRIMARY KEY,
    name JSONB NOT NULL DEFAULT '{}',
    category CHAR(5) REFERENCES matter_category(code) ON DELETE SET NULL ON UPDATE CASCADE,
    country CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    is_task BOOLEAN NOT NULL DEFAULT FALSE,
    status_event BOOLEAN NOT NULL DEFAULT FALSE,
    default_responsible VARCHAR(20),
    use_matter_resp BOOLEAN NOT NULL DEFAULT FALSE,
    killer BOOLEAN NOT NULL DEFAULT FALSE,
    "unique" BOOLEAN NOT NULL DEFAULT FALSE,
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN event_name.is_task IS 'Indicates whether the event can be used as a task';
COMMENT ON COLUMN event_name.status_event IS 'Indicates whether the event defines a new status for the matter';
COMMENT ON COLUMN event_name.use_matter_resp IS 'Use the matter responsible as default responsible';
COMMENT ON COLUMN event_name.killer IS 'Indicates whether this event kills the matter';

-- Matter table
DROP TABLE IF EXISTS matter CASCADE;
CREATE TABLE matter (
    id SERIAL PRIMARY KEY,
    category_code CHAR(5) NOT NULL REFERENCES matter_category(code) ON UPDATE CASCADE,
    caseref VARCHAR(30) NOT NULL,
    country CHAR(2) NOT NULL REFERENCES country(iso) ON UPDATE CASCADE,
    origin CHAR(2) REFERENCES country(iso) ON DELETE SET NULL ON UPDATE CASCADE,
    type_code CHAR(5) REFERENCES matter_type(code) ON DELETE SET NULL ON UPDATE CASCADE,
    idx SMALLINT,
    suffix VARCHAR(16),
    parent_id INTEGER REFERENCES matter(id) ON DELETE SET NULL ON UPDATE CASCADE,
    container_id INTEGER REFERENCES matter(id) ON DELETE SET NULL ON UPDATE CASCADE,
    responsible VARCHAR(20),
    dead BOOLEAN NOT NULL DEFAULT FALSE,
    alt_ref VARCHAR(100),
    notes TEXT,
    expire_date DATE,
    term_adjust SMALLINT NOT NULL DEFAULT 0,
    uid VARCHAR(45),
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN matter.caseref IS 'The case reference, typically the client reference';
COMMENT ON COLUMN matter.origin IS 'For claiming priority or origin of national phase';
COMMENT ON COLUMN matter.idx IS 'Index for distinguishing between matters with the same caseref-country pair';
COMMENT ON COLUMN matter.suffix IS 'Free suffix after idx in UID';
COMMENT ON COLUMN matter.parent_id IS 'Parent matter for continuation/divisional';
COMMENT ON COLUMN matter.container_id IS 'Container matter for shared data (actors, classifiers)';
COMMENT ON COLUMN matter.dead IS 'Indicates whether the matter is dead (abandoned, lapsed, etc.)';
COMMENT ON COLUMN matter.alt_ref IS 'Alternative reference';
COMMENT ON COLUMN matter.expire_date IS 'Calculated expiry date';
COMMENT ON COLUMN matter.term_adjust IS 'Patent term adjustment in days';

CREATE UNIQUE INDEX uqmatter ON matter(caseref, country, origin, type_code, idx);
CREATE INDEX idx_matter_category ON matter(category_code);
CREATE INDEX idx_matter_caseref ON matter(caseref);
CREATE INDEX idx_matter_country ON matter(country);
CREATE INDEX idx_matter_responsible ON matter(responsible);

-- Event table
DROP TABLE IF EXISTS event CASCADE;
CREATE TABLE event (
    id SERIAL PRIMARY KEY,
    code CHAR(5) NOT NULL REFERENCES event_name(code) ON DELETE RESTRICT ON UPDATE CASCADE,
    matter_id INTEGER NOT NULL REFERENCES matter(id) ON DELETE CASCADE ON UPDATE CASCADE,
    event_date DATE,
    alt_matter_id INTEGER REFERENCES matter(id) ON DELETE SET NULL ON UPDATE CASCADE,
    detail VARCHAR(45),
    notes VARCHAR(150),
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT uqevent UNIQUE(matter_id, code, event_date, alt_matter_id)
);

COMMENT ON COLUMN event.code IS 'Link to event_names table';
COMMENT ON COLUMN event.alt_matter_id IS 'Essentially for priority claims. ID of prior patent this event refers to';
COMMENT ON COLUMN event.detail IS 'Numbers or short comments';

CREATE INDEX idx_event_code ON event(code);
CREATE INDEX idx_event_date ON event(event_date);
CREATE INDEX idx_event_detail ON event(detail);

-- Task rules table
DROP TABLE IF EXISTS task_rules CASCADE;
CREATE TABLE task_rules (
    id SERIAL PRIMARY KEY,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    for_category CHAR(5) REFERENCES matter_category(code) ON UPDATE CASCADE,
    for_country CHAR(2) REFERENCES country(iso) ON DELETE CASCADE ON UPDATE CASCADE,
    for_origin CHAR(2) REFERENCES country(iso) ON DELETE CASCADE ON UPDATE CASCADE,
    for_type CHAR(5) REFERENCES matter_type(code) ON DELETE CASCADE ON UPDATE CASCADE,
    task CHAR(5) NOT NULL REFERENCES event_name(code) ON UPDATE CASCADE,
    detail JSONB,
    days SMALLINT NOT NULL DEFAULT 0,
    months SMALLINT NOT NULL DEFAULT 0,
    years SMALLINT NOT NULL DEFAULT 0,
    recurring BOOLEAN NOT NULL DEFAULT FALSE,
    end_of_month BOOLEAN NOT NULL DEFAULT FALSE,
    abort_on CHAR(5) REFERENCES event_name(code) ON DELETE SET NULL ON UPDATE CASCADE,
    condition_event CHAR(5) REFERENCES event_name(code) ON DELETE SET NULL ON UPDATE CASCADE,
    use_priority BOOLEAN NOT NULL DEFAULT FALSE,
    use_before DATE,
    use_after DATE,
    cost DECIMAL(6,2),
    fee DECIMAL(6,2),
    currency CHAR(3) DEFAULT 'EUR',
    trigger_event CHAR(5) NOT NULL REFERENCES event_name(code) ON UPDATE CASCADE,
    clear_task BOOLEAN NOT NULL DEFAULT FALSE,
    delete_task BOOLEAN NOT NULL DEFAULT FALSE,
    responsible VARCHAR(20),
    notes VARCHAR(160),
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN task_rules.active IS 'Indicates whether the rule is active';
COMMENT ON COLUMN task_rules.for_category IS 'Apply to this category';
COMMENT ON COLUMN task_rules.for_country IS 'Apply to this country only';
COMMENT ON COLUMN task_rules.for_origin IS 'Apply to this origin only';
COMMENT ON COLUMN task_rules.for_type IS 'Apply to this type only';
COMMENT ON COLUMN task_rules.task IS 'The task to create';
COMMENT ON COLUMN task_rules.days IS 'Days to add to trigger date';
COMMENT ON COLUMN task_rules.months IS 'Months to add to trigger date';
COMMENT ON COLUMN task_rules.years IS 'Years to add to trigger date';
COMMENT ON COLUMN task_rules.recurring IS 'Is this a recurring task (renewals)';
COMMENT ON COLUMN task_rules.end_of_month IS 'Set due date to end of month';
COMMENT ON COLUMN task_rules.abort_on IS 'Abort task creation if this event exists';
COMMENT ON COLUMN task_rules.condition_event IS 'Only create task if this event exists';
COMMENT ON COLUMN task_rules.use_priority IS 'Use earliest priority date for calculation';
COMMENT ON COLUMN task_rules.trigger_event IS 'The event that triggers this task';
COMMENT ON COLUMN task_rules.clear_task IS 'Mark matching task as done instead of creating';
COMMENT ON COLUMN task_rules.delete_task IS 'Delete matching task instead of creating';

-- Task table
DROP TABLE IF EXISTS task CASCADE;
CREATE TABLE task (
    id SERIAL PRIMARY KEY,
    trigger_id INTEGER NOT NULL REFERENCES event(id) ON DELETE CASCADE ON UPDATE CASCADE,
    code CHAR(5) NOT NULL REFERENCES event_name(code) ON UPDATE CASCADE,
    due_date DATE,
    done BOOLEAN NOT NULL DEFAULT FALSE,
    done_date DATE,
    assigned_to VARCHAR(20),
    detail JSONB,
    notes TEXT,
    step SMALLINT,
    grace_period BOOLEAN,
    invoice_step SMALLINT,
    cost DECIMAL(10,2),
    fee DECIMAL(10,2),
    currency CHAR(3),
    rule_used INTEGER REFERENCES task_rules(id) ON DELETE SET NULL ON UPDATE CASCADE,
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN task.trigger_id IS 'The event that triggered this task';
COMMENT ON COLUMN task.code IS 'Task type code';
COMMENT ON COLUMN task.done IS 'Task completion status';
COMMENT ON COLUMN task.step IS 'Workflow step';
COMMENT ON COLUMN task.invoice_step IS 'Invoicing step';
COMMENT ON COLUMN task.rule_used IS 'The rule that created this task';

CREATE INDEX idx_task_trigger ON task(trigger_id);
CREATE INDEX idx_task_code ON task(code);
CREATE INDEX idx_task_due_date ON task(due_date);
CREATE INDEX idx_task_done ON task(done);

-- Classifier type table
DROP TABLE IF EXISTS classifier_type CASCADE;
CREATE TABLE classifier_type (
    code CHAR(5) NOT NULL PRIMARY KEY,
    type JSONB NOT NULL DEFAULT '{}',
    main_display BOOLEAN NOT NULL DEFAULT FALSE,
    for_category CHAR(5) REFERENCES matter_category(code) ON DELETE SET NULL ON UPDATE CASCADE,
    display_order SMALLINT DEFAULT 127,
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN classifier_type.main_display IS 'Indicates whether to display as main information';
COMMENT ON COLUMN classifier_type.for_category IS 'For showing in the pick-lists of only the selected category';

-- Classifier value table
DROP TABLE IF EXISTS classifier_value CASCADE;
CREATE TABLE classifier_value (
    id SERIAL PRIMARY KEY,
    value VARCHAR(160) NOT NULL,
    type_code CHAR(5) REFERENCES classifier_type(code) ON DELETE SET NULL ON UPDATE CASCADE,
    notes VARCHAR(255),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT uqclvalue UNIQUE(value, type_code)
);

COMMENT ON COLUMN classifier_value.type_code IS 'Restrict this classifier name to the classifier type identified here';

-- Classifier table
DROP TABLE IF EXISTS classifier CASCADE;
CREATE TABLE classifier (
    id SERIAL PRIMARY KEY,
    matter_id INTEGER NOT NULL REFERENCES matter(id) ON DELETE CASCADE ON UPDATE CASCADE,
    type_code CHAR(5) NOT NULL REFERENCES classifier_type(code) ON UPDATE CASCADE,
    value TEXT,
    img BYTEA,
    url VARCHAR(256),
    value_id INTEGER REFERENCES classifier_value(id) ON DELETE SET NULL ON UPDATE CASCADE,
    display_order SMALLINT NOT NULL DEFAULT 1,
    lnk_matter_id INTEGER REFERENCES matter(id) ON DELETE CASCADE ON UPDATE CASCADE,
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN classifier.type_code IS 'Link to classifier_types';
COMMENT ON COLUMN classifier.value IS 'A free-text value used when classifier_values has no record linked to the classifier_types record';
COMMENT ON COLUMN classifier.url IS 'Display value as a link to the URL defined here';
COMMENT ON COLUMN classifier.value_id IS 'Links to the classifier_values table if it has a link to classifier_types';
COMMENT ON COLUMN classifier.lnk_matter_id IS 'Matter this case is linked to';

CREATE INDEX idx_classifier_matter ON classifier(matter_id);
CREATE INDEX idx_classifier_type ON classifier(type_code);

-- Matter actor link table
DROP TABLE IF EXISTS matter_actor_lnk CASCADE;
CREATE TABLE matter_actor_lnk (
    id SERIAL PRIMARY KEY,
    matter_id INTEGER NOT NULL REFERENCES matter(id) ON DELETE CASCADE ON UPDATE CASCADE,
    actor_id INTEGER NOT NULL REFERENCES actor(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    role CHAR(5) NOT NULL REFERENCES actor_role(code) ON DELETE CASCADE ON UPDATE CASCADE,
    display_order SMALLINT NOT NULL DEFAULT 1,
    shared BOOLEAN NOT NULL DEFAULT FALSE,
    actor_ref VARCHAR(45),
    company_id INTEGER REFERENCES actor(id) ON DELETE SET NULL ON UPDATE CASCADE,
    rate DECIMAL(5,2),
    date DATE,
    creator CHAR(16),
    updater CHAR(16),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMENT ON COLUMN matter_actor_lnk.shared IS 'Indicates whether this actor link is shared across the family';
COMMENT ON COLUMN matter_actor_lnk.actor_ref IS 'The actors reference for this matter';
COMMENT ON COLUMN matter_actor_lnk.company_id IS 'The company the actor is working for in this role';

CREATE UNIQUE INDEX uqactor_link ON matter_actor_lnk (matter_id, actor_id, role, COALESCE(company_id, 0));
CREATE INDEX idx_mal_matter ON matter_actor_lnk(matter_id);
CREATE INDEX idx_mal_actor ON matter_actor_lnk(actor_id);
CREATE INDEX idx_mal_role ON matter_actor_lnk(role);

-- Default actor table
DROP TABLE IF EXISTS default_actor CASCADE;
CREATE TABLE default_actor (
    id SERIAL PRIMARY KEY,
    actor_id INTEGER NOT NULL REFERENCES actor(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    role CHAR(5) NOT NULL REFERENCES actor_role(code) ON DELETE CASCADE ON UPDATE CASCADE,
    for_category CHAR(5) REFERENCES matter_category(code) ON DELETE CASCADE ON UPDATE CASCADE,
    for_country CHAR(2) REFERENCES country(iso) ON DELETE CASCADE ON UPDATE CASCADE,
    for_client INTEGER REFERENCES actor(id) ON DELETE CASCADE ON UPDATE CASCADE,
    shared BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_default_actor_actor ON default_actor(actor_id);
CREATE INDEX idx_default_actor_role ON default_actor(role);
CREATE INDEX idx_default_actor_country ON default_actor(for_country);
CREATE INDEX idx_default_actor_client ON default_actor(for_client);

-- Fees table
DROP TABLE IF EXISTS fees CASCADE;
CREATE TABLE fees (
    id SERIAL PRIMARY KEY,
    for_country CHAR(2) NOT NULL REFERENCES country(iso) ON DELETE CASCADE ON UPDATE CASCADE,
    for_category CHAR(5) NOT NULL REFERENCES matter_category(code) ON DELETE CASCADE ON UPDATE CASCADE,
    qt INTEGER NOT NULL,
    use_before DATE,
    use_after DATE,
    cost DECIMAL(10,2),
    fee DECIMAL(10,2),
    cost_reduced DECIMAL(10,2),
    fee_reduced DECIMAL(10,2),
    cost_sup DECIMAL(10,2),
    fee_sup DECIMAL(10,2),
    cost_sup_reduced DECIMAL(10,2),
    fee_sup_reduced DECIMAL(10,2),
    currency CHAR(3) DEFAULT 'EUR',
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE UNIQUE INDEX uqfees ON fees (for_country, for_category, qt, COALESCE(use_before, '9999-12-31'::DATE));

COMMENT ON COLUMN fees.qt IS 'Quantity (typically renewal year)';
COMMENT ON COLUMN fees.cost_reduced IS 'Reduced cost for small entities';
COMMENT ON COLUMN fees.fee_reduced IS 'Reduced fee for small entities';
COMMENT ON COLUMN fees.cost_sup IS 'Supplemental cost (late payment)';
COMMENT ON COLUMN fees.fee_sup IS 'Supplemental fee (late payment)';

-- Template classes table
DROP TABLE IF EXISTS template_classes CASCADE;
CREATE TABLE template_classes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(45) NOT NULL,
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Template members table
DROP TABLE IF EXISTS template_members CASCADE;
CREATE TABLE template_members (
    id SERIAL PRIMARY KEY,
    class_id INTEGER NOT NULL REFERENCES template_classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    language CHAR(2),
    style VARCHAR(45),
    format VARCHAR(45),
    summary VARCHAR(160),
    description TEXT,
    body TEXT,
    notes VARCHAR(160),
    creator VARCHAR(20),
    updater VARCHAR(20),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX idx_template_members_class ON template_members(class_id);

-- Event class link table (for template association)
DROP TABLE IF EXISTS event_class_lnk CASCADE;
CREATE TABLE event_class_lnk (
    id SERIAL PRIMARY KEY,
    event_name_code CHAR(5) NOT NULL REFERENCES event_name(code) ON DELETE CASCADE ON UPDATE CASCADE,
    template_class_id INTEGER NOT NULL REFERENCES template_classes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT uq_event_class UNIQUE(event_name_code, template_class_id)
);

-- Renewals logs table
DROP TABLE IF EXISTS renewals_logs CASCADE;
CREATE TABLE renewals_logs (
    id SERIAL PRIMARY KEY,
    task_id INTEGER NOT NULL REFERENCES task(id) ON DELETE CASCADE ON UPDATE CASCADE,
    from_step SMALLINT,
    to_step SMALLINT,
    from_invoice_step SMALLINT,
    to_invoice_step SMALLINT,
    from_done BOOLEAN,
    to_done BOOLEAN,
    created_at TIMESTAMP,
    creator VARCHAR(20)
);

CREATE INDEX idx_renewals_logs_task ON renewals_logs(task_id);

-- Password resets table (Laravel standard)
DROP TABLE IF EXISTS password_resets CASCADE;
CREATE TABLE password_resets (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP
);

CREATE INDEX idx_password_resets_email ON password_resets(email);

-- Failed jobs table (Laravel standard)
DROP TABLE IF EXISTS failed_jobs CASCADE;
CREATE TABLE failed_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    exception TEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Migrations table (Laravel standard)
DROP TABLE IF EXISTS migrations CASCADE;
CREATE TABLE migrations (
    id SERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

-- ============================================================================
-- HELPER FUNCTIONS
-- ============================================================================

-- Function to convert text to title case
CREATE OR REPLACE FUNCTION tcase(str TEXT) RETURNS TEXT AS $$
DECLARE
    result TEXT := '';
    word TEXT;
    words TEXT[];
    i INTEGER;
BEGIN
    IF str IS NULL THEN
        RETURN NULL;
    END IF;

    words := string_to_array(str, ' ');
    FOR i IN 1..array_length(words, 1) LOOP
        word := words[i];
        IF length(word) > 0 THEN
            result := result || upper(left(word, 1)) || lower(substring(word from 2));
            IF i < array_length(words, 1) THEN
                result := result || ' ';
            END IF;
        END IF;
    END LOOP;

    RETURN result;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Function to get actor list for a matter and role
CREATE OR REPLACE FUNCTION actor_list(mid INTEGER, arole TEXT) RETURNS TEXT AS $$
DECLARE
    result TEXT;
BEGIN
    SELECT string_agg(COALESCE(actor.display_name, actor.name), ', ' ORDER BY matter_actor_lnk.display_order)
    INTO result
    FROM matter_actor_lnk
    JOIN actor ON actor.id = matter_actor_lnk.actor_id
    WHERE matter_actor_lnk.matter_id = mid
    AND matter_actor_lnk.role = arole;

    RETURN result;
END;
$$ LANGUAGE plpgsql STABLE;

-- Function to get matter status
CREATE OR REPLACE FUNCTION matter_status(mid INTEGER) RETURNS TEXT AS $$
DECLARE
    result TEXT;
BEGIN
    SELECT event_name.name ->> 'en'
    INTO result
    FROM event
    JOIN event_name ON event.code = event_name.code
    WHERE event.matter_id = mid
    AND event_name.status_event = TRUE
    ORDER BY event.event_date DESC
    LIMIT 1;

    RETURN result;
END;
$$ LANGUAGE plpgsql STABLE;

-- ============================================================================
-- TRIGGER FUNCTIONS
-- ============================================================================

-- Classifier before insert trigger function
CREATE OR REPLACE FUNCTION classifier_before_insert_func() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.type_code = 'TITEN' THEN
        NEW.value := tcase(NEW.value);
    ELSIF NEW.type_code IN ('TIT', 'TITOF', 'TITAL') THEN
        NEW.value := upper(left(NEW.value, 1)) || lower(substring(NEW.value from 2));
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER classifier_before_insert
    BEFORE INSERT ON classifier
    FOR EACH ROW EXECUTE FUNCTION classifier_before_insert_func();

-- Event before insert trigger function
CREATE OR REPLACE FUNCTION event_before_insert_func() RETURNS TRIGGER AS $$
DECLARE
    vdate DATE;
BEGIN
    IF NEW.alt_matter_id IS NOT NULL THEN
        SELECT event_date INTO vdate
        FROM event
        WHERE code = 'FIL' AND matter_id = NEW.alt_matter_id;

        IF vdate IS NOT NULL THEN
            NEW.event_date := vdate;
        ELSE
            NEW.event_date := CURRENT_DATE;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER event_before_insert
    BEFORE INSERT ON event
    FOR EACH ROW EXECUTE FUNCTION event_before_insert_func();

-- Event before update trigger function
CREATE OR REPLACE FUNCTION event_before_update_func() RETURNS TRIGGER AS $$
DECLARE
    vdate DATE;
BEGIN
    IF NEW.alt_matter_id IS NOT NULL THEN
        SELECT event_date INTO vdate
        FROM event
        WHERE code = 'FIL' AND matter_id = NEW.alt_matter_id;

        IF vdate IS NOT NULL THEN
            NEW.event_date := vdate;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER event_before_update
    BEFORE UPDATE ON event
    FOR EACH ROW EXECUTE FUNCTION event_before_update_func();

-- Function to compute matter uid
CREATE OR REPLACE FUNCTION compute_matter_uid(
    p_caseref VARCHAR,
    p_country VARCHAR,
    p_origin VARCHAR,
    p_type_code VARCHAR,
    p_idx SMALLINT
) RETURNS VARCHAR AS $$
BEGIN
    RETURN p_caseref || p_country ||
        CASE WHEN p_origin IS NOT NULL THEN '-' || p_origin ELSE '' END ||
        CASE WHEN p_type_code IS NOT NULL THEN '-' || p_type_code ELSE '' END ||
        CASE WHEN p_idx IS NOT NULL THEN '.' || p_idx::TEXT ELSE '' END;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Matter before insert trigger function
CREATE OR REPLACE FUNCTION matter_before_insert_func() RETURNS TRIGGER AS $$
BEGIN
    NEW.uid := compute_matter_uid(NEW.caseref, NEW.country, NEW.origin, NEW.type_code, NEW.idx);
    NEW.created_at := CURRENT_TIMESTAMP;
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER matter_before_insert
    BEFORE INSERT ON matter
    FOR EACH ROW EXECUTE FUNCTION matter_before_insert_func();

-- Matter before update trigger function
CREATE OR REPLACE FUNCTION matter_before_update_func() RETURNS TRIGGER AS $$
BEGIN
    NEW.uid := compute_matter_uid(NEW.caseref, NEW.country, NEW.origin, NEW.type_code, NEW.idx);
    NEW.updater := current_user;
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER matter_before_update
    BEFORE UPDATE ON matter
    FOR EACH ROW EXECUTE FUNCTION matter_before_update_func();

-- Task before insert trigger function
CREATE OR REPLACE FUNCTION task_before_insert_func() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.due_date IS NOT NULL AND NEW.done IS NULL THEN
        IF NEW.due_date <= CURRENT_DATE THEN
            NEW.done := TRUE;
            NEW.done_date := NEW.due_date;
        ELSE
            NEW.done := FALSE;
        END IF;
    END IF;
    NEW.created_at := CURRENT_TIMESTAMP;
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER task_before_insert
    BEFORE INSERT ON task
    FOR EACH ROW EXECUTE FUNCTION task_before_insert_func();

-- Task before update trigger function
CREATE OR REPLACE FUNCTION task_before_update_func() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.done = TRUE AND OLD.done = FALSE THEN
        IF NEW.done_date IS NULL THEN
            NEW.done_date := CURRENT_DATE;
        END IF;
    END IF;
    IF NEW.done = FALSE AND OLD.done = TRUE THEN
        NEW.done_date := NULL;
    END IF;
    NEW.updated_at := CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER task_before_update
    BEFORE UPDATE ON task
    FOR EACH ROW EXECUTE FUNCTION task_before_update_func();

-- ============================================================================
-- STORED PROCEDURES (as PostgreSQL functions)
-- ============================================================================

-- Procedure to insert recurring renewals
CREATE OR REPLACE FUNCTION insert_recurring_renewals(
    p_trigger_id INTEGER,
    p_rule_id INTEGER,
    p_base_date DATE,
    p_responsible VARCHAR(16),
    p_user VARCHAR(16)
) RETURNS VOID AS $$
DECLARE
    first_renewal INTEGER;
    r_year INTEGER;
    base_date DATE;
    start_date DATE;
    due_date DATE;
    expiry_date DATE;
    origin CHAR(2);
BEGIN
    SELECT ebase.event_date, estart.event_date, country.renewal_first, matter.expire_date, matter.origin
    INTO base_date, start_date, first_renewal, expiry_date, origin
    FROM country
    JOIN matter ON country.iso = matter.country
    JOIN event estart ON estart.matter_id = matter.id AND estart.id = p_trigger_id
    JOIN event ebase ON ebase.matter_id = matter.id
    WHERE country.renewal_start = estart.code
    AND country.renewal_base = ebase.code;

    -- Leave if the country has no parameters
    IF start_date IS NULL THEN
        RETURN;
    END IF;

    base_date := LEAST(base_date, p_base_date);
    r_year := ABS(first_renewal);

    WHILE r_year <= 20 LOOP
        IF first_renewal > 0 THEN
            due_date := base_date + ((r_year - 1) * INTERVAL '1 year');
        ELSE
            due_date := start_date + ((r_year - 1) * INTERVAL '1 year');
        END IF;

        IF due_date > expiry_date THEN
            RETURN;
        END IF;

        IF due_date < start_date THEN
            due_date := start_date;
        END IF;

        -- Ignore renewals in the past beyond grace period
        IF (due_date < CURRENT_DATE - INTERVAL '6 months' AND origin != 'WO')
           OR (due_date < CURRENT_DATE - INTERVAL '19 months' AND origin = 'WO') THEN
            r_year := r_year + 1;
            CONTINUE;
        END IF;

        INSERT INTO task (trigger_id, code, due_date, detail, rule_used, assigned_to, creator, created_at, updated_at)
        VALUES (p_trigger_id, 'REN', due_date, jsonb_build_object('en', r_year::TEXT), p_rule_id, p_responsible, p_user, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

        r_year := r_year + 1;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Procedure to update expired matters
CREATE OR REPLACE FUNCTION update_expired() RETURNS VOID AS $$
DECLARE
    rec RECORD;
BEGIN
    FOR rec IN
        SELECT matter.id, matter.expire_date
        FROM matter
        WHERE expire_date < CURRENT_DATE AND dead = FALSE
    LOOP
        INSERT INTO event (code, matter_id, event_date, created_at, creator, updated_at)
        VALUES ('EXP', rec.id, rec.expire_date, CURRENT_TIMESTAMP, 'system', CURRENT_TIMESTAMP)
        ON CONFLICT DO NOTHING;
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- ============================================================================
-- VIEWS
-- ============================================================================

-- Event link list view (handles priority claims with linked matters)
CREATE OR REPLACE VIEW event_lnk_list AS
SELECT
    event.id,
    event.code,
    event.matter_id,
    CASE WHEN event.alt_matter_id IS NULL THEN event.event_date ELSE lnk.event_date END AS event_date,
    CASE WHEN event.alt_matter_id IS NULL THEN event.detail ELSE lnk.detail END AS detail,
    matter.country
FROM event
LEFT JOIN event lnk ON event.alt_matter_id = lnk.matter_id AND lnk.code = 'FIL'
LEFT JOIN matter ON event.alt_matter_id = matter.id;

-- Matter actors view
CREATE OR REPLACE VIEW matter_actors AS
SELECT
    pivot.id,
    actor.id AS actor_id,
    COALESCE(actor.display_name, actor.name) AS display_name,
    actor.name,
    actor.first_name,
    actor.email,
    pivot.display_order,
    pivot.role AS role_code,
    actor_role.name ->> 'en' AS role_name,
    actor_role.shareable,
    actor_role.show_ref,
    actor_role.show_company,
    actor_role.show_rate,
    actor_role.show_date,
    matter.id AS matter_id,
    actor.warn,
    pivot.actor_ref,
    pivot.date,
    pivot.rate,
    pivot.shared,
    co.name AS company,
    CASE WHEN pivot.matter_id = matter.container_id THEN 1 ELSE 0 END AS inherited
FROM matter_actor_lnk pivot
JOIN matter ON pivot.matter_id = matter.id OR (pivot.shared = TRUE AND pivot.matter_id = matter.container_id)
JOIN actor ON pivot.actor_id = actor.id
LEFT JOIN actor co ON co.id = pivot.company_id
JOIN actor_role ON pivot.role = actor_role.code
ORDER BY actor_role.display_order, pivot.display_order;

-- Matter classifiers view
CREATE OR REPLACE VIEW matter_classifiers AS
SELECT
    classifier.id,
    matter.id AS matter_id,
    classifier.type_code,
    classifier_type.type ->> 'en' AS type_name,
    classifier_type.main_display,
    CASE WHEN classifier.value_id IS NULL THEN classifier.value ELSE classifier_value.value END AS value,
    classifier.url,
    classifier.lnk_matter_id,
    classifier.display_order
FROM classifier
JOIN classifier_type ON classifier.type_code = classifier_type.code
JOIN matter ON COALESCE(matter.container_id, matter.id) = classifier.matter_id
LEFT JOIN classifier_value ON classifier_value.id = classifier.value_id
ORDER BY classifier_type.display_order, classifier.display_order;

-- Task list view
CREATE OR REPLACE VIEW task_list AS
SELECT
    task.id,
    task.code,
    event_name.name ->> 'en' AS name,
    task.detail,
    task.due_date,
    task.done,
    task.done_date,
    event.matter_id,
    task.cost,
    task.fee,
    task.trigger_id,
    matter.category_code AS category,
    matter.caseref,
    matter.country,
    matter.origin,
    matter.type_code,
    matter.idx,
    COALESCE(task.assigned_to, matter.responsible) AS responsible,
    actor.login AS delegate,
    task.rule_used,
    matter.dead
FROM matter
LEFT JOIN matter_actor_lnk ON COALESCE(matter.container_id, matter.id) = matter_actor_lnk.matter_id AND matter_actor_lnk.role = 'DEL'
LEFT JOIN actor ON actor.id = matter_actor_lnk.actor_id
JOIN event ON matter.id = event.matter_id
JOIN task ON task.trigger_id = event.id
JOIN event_name ON task.code = event_name.code;

-- Users view (for Laravel authentication)
CREATE OR REPLACE VIEW users AS
SELECT
    id,
    login AS name,
    login,
    email,
    password,
    default_role,
    language,
    remember_token,
    created_at,
    updated_at
FROM actor
WHERE login IS NOT NULL;

-- ============================================================================
-- AUDIT LOGS TABLE
-- ============================================================================
-- Audit log table for tracking all data changes for compliance and dispute resolution.
-- Captures who/what/when for every create, update, and delete operation on auditable models.
DROP TABLE IF EXISTS audit_logs CASCADE;
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    auditable_type VARCHAR(100) NOT NULL,
    auditable_id BIGINT NOT NULL,
    action VARCHAR(20) NOT NULL,
    user_login VARCHAR(16),
    user_name VARCHAR(100),
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON COLUMN audit_logs.auditable_type IS 'Model class name (e.g., App\\Models\\Matter)';
COMMENT ON COLUMN audit_logs.auditable_id IS 'Primary key of the audited record';
COMMENT ON COLUMN audit_logs.action IS 'Type of action: created, updated, deleted';
COMMENT ON COLUMN audit_logs.user_login IS 'Login of user who made the change';
COMMENT ON COLUMN audit_logs.user_name IS 'Full name of user at time of action';
COMMENT ON COLUMN audit_logs.old_values IS 'Previous values before change (for update/delete)';
COMMENT ON COLUMN audit_logs.new_values IS 'New values after change (for create/update)';
COMMENT ON COLUMN audit_logs.ip_address IS 'IP address of the client';
COMMENT ON COLUMN audit_logs.user_agent IS 'Browser/client user agent';
COMMENT ON COLUMN audit_logs.url IS 'URL where the action was triggered';
COMMENT ON COLUMN audit_logs.created_at IS 'When the action occurred';

CREATE INDEX audit_logs_auditable_index ON audit_logs (auditable_type, auditable_id);
CREATE INDEX audit_logs_user_index ON audit_logs (user_login);
CREATE INDEX audit_logs_action_index ON audit_logs (action);
CREATE INDEX audit_logs_created_index ON audit_logs (created_at);

-- ============================================================================
-- INITIAL DATA SEED (sample roles)
-- ============================================================================

INSERT INTO actor_role (code, name, display_order, shareable, show_ref) VALUES
    ('CLI', '{"en": "Client"}', 1, TRUE, TRUE),
    ('AGT', '{"en": "Agent"}', 2, TRUE, TRUE),
    ('APP', '{"en": "Applicant"}', 3, TRUE, FALSE),
    ('INV', '{"en": "Inventor"}', 4, TRUE, FALSE),
    ('OWN', '{"en": "Owner"}', 5, TRUE, FALSE),
    ('ANN', '{"en": "Annuity Agent"}', 6, TRUE, TRUE),
    ('DEL', '{"en": "Delegate"}', 7, FALSE, FALSE),
    ('TRS', '{"en": "Translator"}', 8, TRUE, TRUE),
    ('WRI', '{"en": "Writer"}', 9, FALSE, FALSE),
    ('PAY', '{"en": "Payor"}', 10, TRUE, TRUE)
ON CONFLICT (code) DO NOTHING;

-- Sample database user roles (not actor roles)
INSERT INTO actor_role (code, name, display_order) VALUES
    ('DBA', '{"en": "Database Admin"}', 100),
    ('DBRW', '{"en": "Read-Write User"}', 101),
    ('DBRO', '{"en": "Read-Only User"}', 102)
ON CONFLICT (code) DO NOTHING;
