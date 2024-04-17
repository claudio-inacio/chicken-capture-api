-- noinspection SqlNoDataSourceInspectionForFile
create schema if not exists authentication;
create schema if not exists main;
create schema if not exists contracting_company;
create schema if not exists catch;
create schema if not exists vehicles;
create schema if not exists financial;

drop table if exists authentication.access_group cascade;
drop table if exists authentication.credential cascade;
drop table if exists authentication.person cascade;
drop table if exists catch.catch_type cascade;
drop table if exists catch.catchs_cancelled cascade;
drop table if exists catch.catchs_configuration cascade;
drop table if exists catch.daily_catch cascade;
drop table if exists contracting_company.contracting_company cascade;
drop table if exists contracting_company.integrated cascade;
drop table if exists financial.financial_accounts cascade;
drop table if exists financial.monthly_closing_reports cascade;
drop table if exists main.collectors cascade;
drop table if exists main.company cascade;
drop table if exists main.company_group cascade;
drop table if exists main.credential_company cascade;
drop table if exists main.team cascade;
drop table if exists main.units cascade;
drop table if exists vehicles.driver_area cascade;
drop table if exists vehicles.vehicles cascade;

CREATE TABLE authentication.access_group
(
    id   serial primary key,
    name varchar(100) not null
);
-- Table to store user information
CREATE TABLE main.company_group
(
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at timestamp,
    updated_at timestamp
);
-- Table to store information about companies
CREATE TABLE main.company
(
    id               SERIAL PRIMARY KEY,
    name             VARCHAR(100) NOT NULL UNIQUE,
    address          VARCHAR(255),
    phone            VARCHAR(20),
    cnpj             VARCHAR(20),
    email            VARCHAR(100),
    company_group_id INT          NOT NULL references main.company_group (id),
    parent_id        INT                   DEFAULT NULL, -- REFERENCIA ELA MESMA IDEIA DE ARVORES
    is_main          boolean      not null default true UNIQUE,
    created_at       timestamp,
    updated_at       timestamp
);

-- Table to store user information
CREATE TABLE authentication.person
(
    id               SERIAL PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    email           VARCHAR(100) UNIQUE,
    phone_number    varchar(15),
    company_group_id INT          NOT NULL references main.company_group (id),
    access_group_id INT          NOT NULL references authentication.access_group (id),
    is_owner         boolean      not null default false,
    created_at       timestamp,
    updated_at       timestamp
);

CREATE TABLE authentication.credential
(
    id              SERIAL PRIMARY KEY,
    document        VARCHAR(14)  NOT NULL,
    password        VARCHAR(100) NOT NULL,
    person_id INT          NOT NULL references authentication.person (id),
    company_id      int not null REFERENCES main.company(id),
    created_at      timestamp,
    updated_at      timestamp
);

CREATE TABLE main.credential_company
(
    credential_id INT REFERENCES authentication.credential (id),
    company_id    INT REFERENCES main.company (id),
    created_at    timestamp,
    updated_at    timestamp
);

-- Empresa contratante, GT FOODS, SADIA E ETC... (Quem contrata o servico do VT BISCOLA)
CREATE TABLE contracting_company.contracting_company
(
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    company_id INT REFERENCES main.company (id),
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE contracting_company.integrated
(
    id                     serial primary key,
    name                   varchar(255) not null,
    contracting_company_id int          not null references contracting_company.contracting_company (id),
    created_at             timestamp,
    updated_at             timestamp
);

-- Ter a info de quantos coletores/apanhadores a empresa tem e qual valor paga.
CREATE TABLE main.collectors
(
    id           serial primary key,
    quantity     int not null,
    salary_value DECIMAL(10, 2),
    company_id   INT REFERENCES main.company (id),
    created_at   timestamp,
    updated_at   timestamp
);

CREATE TABLE main.units
(
    id                     SERIAL PRIMARY KEY,
    name                   VARCHAR(100) NOT NULL,
    location               VARCHAR(100) NOT NULL,
    company_id             INT REFERENCES main.company (id),
    contracting_company_id INT REFERENCES contracting_company.contracting_company (id)
);
CREATE TABLE main.team
(
    id                     SERIAL PRIMARY KEY,
    name                   VARCHAR(100) NOT NULL,
    default_unit_id        INT REFERENCES main.units (id),
    company_id             INT REFERENCES main.company (id),
    quantity_collectors    int          not null,
    contracting_company_id INT REFERENCES contracting_company.contracting_company (id),
    created_at             timestamp,
    updated_at             timestamp
);
CREATE TABLE catch.catch_type
(
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE catch.catch_daily
(
    id              SERIAL PRIMARY KEY,
    date            DATE NOT NULL,
    quantity        INT  NOT NULL,
    code            INT,
    batch           varchar(50),
    total_cancelled INT,
    credential_id   INT REFERENCES authentication.credential (id),
    units_id        INT REFERENCES main.units (id),
    integrated_id   INT REFERENCES contracting_company.integrated (id),
    team_id         INT REFERENCES main.team (id),
    catch_type_id   INT  NOT NULL REFERENCES catch.catch_type (id),
    company_id      INT REFERENCES main.company (id),
    created_at      timestamp,
    updated_at      timestamp
);
-- media de cancelamentos por equipe
-- media de cancelamentos por dia
CREATE TABLE catch.catchs_configuration
(
    id                 SERIAL PRIMARY KEY,
    catch_type_id      INT            NOT NULL REFERENCES catch.catch_type (id),
    company_id         INT REFERENCES main.company (id),
    catch_price        DECIMAL(12, 2) NOT NULL,
    cancellation_price DECIMAL(12, 2) NOT NULL,
    UNIQUE (company_id, catch_type_id),
    created_at         timestamp,
    updated_at         timestamp
);
CREATE TABLE catch.catchs_cancelled
(
    id             SERIAL PRIMARY KEY,
    date           DATE NOT NULL,
    credential_id  INT REFERENCES authentication.credential (id),
    quantity       INT  NOT NULL,
    daily_catch_id INT REFERENCES catch.daily_catch (id),
    company_id     INT REFERENCES main.company (id),
    notes          VARCHAR(100) DEFAULT 'Nao contem',
    created_at     timestamp,
    updated_at     timestamp
);


CREATE TABLE vehicles.vehicles
(
    vehicle_id   SERIAL PRIMARY KEY,
    vehicle_name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20)  NOT NULL,
    unit_id      INT REFERENCES main.units (id),
    company_id   INT REFERENCES main.company (id),
    created_at   timestamp,
    updated_at   timestamp
);

CREATE TABLE vehicles.driver_Area
(
    area_id              SERIAL PRIMARY KEY,
    credential_id        INT REFERENCES authentication.credential (id),
    vehicle_id           INT REFERENCES vehicles.vehicles (vehicle_id),
    fuel                 INTEGER        NOT NULL,
    maintenance_expenses DECIMAL(12, 2) NOT NULL,
    mileage              INTEGER        NOT NULL,
    daily_start_km       INTEGER,
    daily_start_time     TIME,
    daily_end_km         INTEGER,
    daily_end_date       timestamp,
    CONSTRAINT unique_user_vehicle UNIQUE (credential_id, vehicle_id),
    company_id           INT REFERENCES main.company (id),
    created_at           timestamp,
    updated_at           timestamp
);

-- Table to record accounts payable
CREATE TABLE financial.financial_accounts
(
    id            SERIAL PRIMARY KEY,
    description   TEXT           NOT NULL,
    amount        DECIMAL(12, 2) NOT NULL,
    due_date      DATE           NOT NULL,
    finished_data TIMESTAMP,
    "type"        int, -- 0 receber ou 1 pagar
    credential_id INT REFERENCES authentication.credential (id),
    company_id    INT REFERENCES main.company (id),
    created_at    timestamp,
    updated_at    timestamp
);
-- Table to store monthly closing reports
CREATE TABLE financial.monthly_closing_reports
(
    id             SERIAL PRIMARY KEY,
    month          INT            NOT NULL,
    year           INT            NOT NULL,
    total_expenses DECIMAL(12, 2) NOT NULL,
    total_income   DECIMAL(12, 2) NOT NULL,
    credential_id  INT REFERENCES authentication.credential (id),
    company_id     INT REFERENCES main.company (id),
    created_at     timestamp,
    updated_at     timestamp
);
