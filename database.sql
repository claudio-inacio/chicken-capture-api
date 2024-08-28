--::::::::::::::::::::::::::::::::::::::::: REMOVER TABELAS ::::::::::::::::::::::::::::::::::::::::::::::::::::::-
drop schema if exists catch cascade;
drop schema if exists vehicles cascade;
drop schema if exists financial cascade;
alter table IF EXISTS main.units drop constraint if exists units_contracting_company_id_fkey cascade;
alter table IF EXISTS main.team drop constraint if exists team_contracting_company_id_fkey cascade;
drop schema if exists contracting_company cascade;
alter table IF EXISTS authentication.person drop constraint if exists person_company_group_id_fkey cascade;
alter table IF EXISTS authentication.credential drop constraint if exists credential_company_id_fkey cascade;
drop schema if exists main cascade;
drop schema if exists authentication cascade;


create schema if not exists main;
create schema if not exists authentication;
create schema if not exists catch;
create schema if not exists vehicles;
create schema if not exists financial;
create schema if not exists main;
create schema if not exists authentication;
create schema if not exists contracting_company;

--::::::::::::::::::::::::::::::::::::::::: CRIAR TABELAS ::::::::::::::::::::::::::::::::::::::::::::::::::::::-
create sequence vehicles.vehicles_vehicle_id_seq
    as integer;

alter sequence vehicles.vehicles_vehicle_id_seq owner to postgres;

create sequence vehicles.driver_area_area_id_seq
    as integer;

alter sequence vehicles.driver_area_area_id_seq owner to postgres;

create table if not exists authentication.access_group
(
    id   serial
        primary key,
    name varchar(100) not null
);

alter table authentication.access_group
    owner to postgres;

create table if not exists main.company_group
(
    id         serial
        primary key,
    name       varchar(100)         not null,
    created_at timestamp,
    updated_at timestamp,
    enabled    boolean default true not null
);

alter table main.company_group
    owner to postgres;

create table if not exists main.company
(
    id               serial
        primary key,
    name             varchar(100)          not null,
    address          varchar(255),
    phone            varchar(20),
    cnpj             varchar(20),
    email            varchar(100),
    company_group_id integer               not null
        references main.company_group,
    parent_id        integer,
    is_main          boolean default false not null,
    created_at       timestamp,
    updated_at       timestamp,
    enabled          boolean default true  not null
);

alter table main.company
    owner to postgres;

create table if not exists main.contracting_company
(
    id         serial
        primary key,
    name       varchar(100)         not null,
    company_id integer
        references main.company,
    created_at timestamp,
    updated_at timestamp,
    enabled    boolean default true not null
);

alter table main.contracting_company
    owner to postgres;

create table if not exists main.integrated
(
    id                     serial
        primary key,
    name                   varchar(255)         not null,
    contracting_company_id integer              not null
        references main.contracting_company,
    created_at             timestamp,
    updated_at             timestamp,
    enabled                boolean default true not null
);

alter table main.integrated
    owner to postgres;

create table if not exists main.collectors
(
    id           serial
        primary key,
    quantity     integer              not null,
    salary_value numeric(10, 2),
    company_id   integer
        references main.company,
    created_at   timestamp,
    updated_at   timestamp,
    enabled      boolean default true not null
);

alter table main.collectors
    owner to postgres;

create table if not exists main.units
(
    id                     serial
        primary key,
    name                   varchar(100)         not null,
    location               varchar(100)         not null,
    company_id             integer
        references main.company,
    contracting_company_id integer
        references main.contracting_company,
    created_at             timestamp,
    updated_at             timestamp,
    enabled                boolean default true not null
);

alter table main.units
    owner to postgres;

create table if not exists main.team
(
    id                     serial
        primary key,
    name                   varchar(100)         not null,
    default_unit_id        integer
        references main.units,
    company_id             integer
        references main.company,
    quantity_collectors    integer              not null,
    contracting_company_id integer
        references main.contracting_company,
    created_at             timestamp,
    updated_at             timestamp,
    enabled                boolean default true not null
);

alter table main.team
    owner to postgres;

create table if not exists catch.catch_type
(
    id         serial
        primary key,
    name       varchar(100)         not null,
    created_at timestamp,
    updated_at timestamp,
    enabled    boolean default true not null
);

alter table catch.catch_type
    owner to postgres;

create table if not exists catch.catchs_configuration
(
    id                 serial
        primary key,
    catch_type_id      integer              not null
        references catch.catch_type,
    company_id         integer
        references main.company,
    catch_price        numeric(12, 2)       not null,
    cancellation_price numeric(12, 2)       not null,
    created_at         timestamp,
    updated_at         timestamp,
    enabled            boolean default true not null,
    unique (company_id, catch_type_id)
);

alter table catch.catchs_configuration
    owner to postgres;

create table if not exists vehicles.vehicle
(
    id           integer default nextval('vehicles.vehicles_vehicle_id_seq'::regclass) not null
        constraint vehicles_pkey
            primary key,
    name varchar(100)                                                          not null,
    plate_number varchar(20)                                                           not null,
    unit_id      integer
        constraint vehicles_unit_id_fkey
            references main.units,
    company_id   integer
        constraint vehicles_company_id_fkey
            references main.company,
    created_at   timestamp,
    updated_at   timestamp,
    enabled      boolean default true                                                  not null,
    mileage      integer default 0
);

alter table vehicles.vehicle
    owner to postgres;

alter sequence vehicles.vehicles_vehicle_id_seq owned by vehicles.vehicle.id;

create unique index if not exists vehicle_plate_number_uindex
    on vehicles.vehicle (plate_number);

create table if not exists financial.monthly_closing_reports
(
    id             serial
        primary key,
    month          integer        not null,
    year           integer        not null,
    total_expenses numeric(12, 2) not null,
    total_income   numeric(12, 2) not null,
    company_id     integer
        references main.company,
    created_at     timestamp,
    updated_at     timestamp
);

alter table financial.monthly_closing_reports
    owner to postgres;

create table if not exists authentication.person
(
    id               serial
        primary key,
    name             varchar(100)          not null,
    email            varchar(100)
        unique,
    phone_number     varchar(15),
    company_group_id integer               not null
        references main.company_group,
    access_group_id  integer               not null
        references authentication.access_group,
    is_owner         boolean default false not null,
    created_at       timestamp,
    updated_at       timestamp,
    enabled          boolean default true  not null
);

alter table authentication.person
    owner to postgres;

create unique index if not exists person_phone_number_uindex
    on authentication.person (phone_number);

create table if not exists authentication.credential
(
    id         serial
        primary key,
    document   varchar(14)  not null,
    password   varchar(100) not null,
    person_id  integer      not null
        references authentication.person,
    company_id integer      not null
        references main.company,
    created_at timestamp,
    updated_at timestamp
);

alter table authentication.credential
    owner to postgres;

create table if not exists authentication.credential_company
(
    credential_id integer
        constraint credential_company_credential_id_fk
            references authentication.credential NOT NULL,
    company_id    integer
        references main.company,
    created_at    timestamp,
    updated_at    timestamp,
    enabled       boolean default true not null,
    id            serial
        constraint credential_company_pk
            primary key
);

alter table authentication.credential_company
    owner to postgres;

create table if not exists vehicles.driver_area
(
    id                   integer        default nextval('vehicles.driver_area_area_id_seq'::regclass) not null
        primary key,
    credential_id        integer
        constraint driver_area_credential_id_fk
            references authentication.credential,
    vehicle_id           integer
        constraint driver_area_vehicle_id_fk
            references vehicles.vehicle,
    liters_of_fuel       integer        default 0,
    maintenance_expenses numeric(12, 2)                                                               not null,
    daily_start_km       integer,
    daily_start_time     time,
    daily_end_km         integer,
    daily_end_date       timestamp,
    company_id           integer
        references main.company,
    created_at           timestamp,
    updated_at           timestamp,
    total_supply_value   numeric(10, 2) default 0,
    enabled              boolean        default true                                                  not null
);

alter table vehicles.driver_area
    owner to postgres;

alter sequence vehicles.driver_area_area_id_seq owned by vehicles.driver_area.id;

create table if not exists financial.financial_accounts
(
    id                 serial
        primary key,
    description        text                 not null,
    amount             numeric(12, 2)       not null,
    due_date           timestamp            not null,
    finished_data      timestamp,
    type               integer,
    credential_id      integer
        constraint financial_accounts_credential_id_fk
            references authentication.credential,
    company_id         integer
        references main.company,
    created_at         timestamp,
    updated_at         timestamp,
    enabled            boolean default true not null,
    reference_id       integer              not null,
    table_reference_id integer              not null,
    status_id          integer              not null
);

alter table financial.financial_accounts
    owner to postgres;

create table if not exists catch.catch_daily
(
    id            serial
        primary key,
    date          date                 not null,
    quantity      integer              not null,
    code          integer,
    batch         varchar(50),
    credential_id integer              not null
        references authentication.credential,
    units_id      integer
        references main.units,
    integrated_id integer
        references main.integrated,
    team_id       integer
        references main.team,
    catch_type_id integer              not null
        references catch.catch_type,
    company_id    integer              not null
        references main.company,
    created_at    timestamp,
    updated_at    timestamp,
    enabled       boolean default true not null
);

alter table catch.catch_daily
    owner to postgres;

create table if not exists catch.catchs_cancelled
(
    id             serial
        primary key,
    date           date                      not null,
    credential_id  integer
        constraint catchs_cancelled_credential__fk
            references authentication.credential,
    quantity       integer                   not null,
    catch_daily_id integer
        constraint catchs_cancelled_catch_daily_id_fk
            references catch.catch_daily,
    company_id     integer
        references main.company,
    notes          varchar(100) default 'Nao contem'::character varying,
    created_at     timestamp,
    updated_at     timestamp,
    enabled        boolean      default true not null
);

alter table catch.catchs_cancelled
    owner to postgres;

