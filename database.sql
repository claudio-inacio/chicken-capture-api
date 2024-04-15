-- noinspection SqlNoDataSourceInspectionForFile
CREATE TABLE "access_group"(
    id serial primary key,
    name varchar(100) not null
);
-- Table to store user information
CREATE TABLE company_group (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);
-- Table to store user information
CREATE TABLE person (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    document VARCHAR(14) NOT NULL,
    company_group_id INT NOT NULL references company_group(id),
    access_group_id INT NOT NULL references access_group(id),
    is_owner boolean not null default false
);

-- Table to store information about companies
CREATE TABLE company (
     id SERIAL PRIMARY KEY,
     name VARCHAR(100) NOT NULL UNIQUE,
     address VARCHAR(255),
     phone VARCHAR(20),
     cnpj VARCHAR(20),
     email VARCHAR(100),
     company_group_id INT NOT NULL references company_group(id),
     parent_id INT DEFAULT NULL, -- REFERENCIA ELA MESMA IDEIA DE ARVORES
     is_main boolean not null default true UNIQUE
);

-- Table to store user information
CREATE TABLE credential (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    access_group_id INT NOT NULL references access_group(id),
    person_id INT NOT NULL references person(id)
);

CREATE TABLE credential_company (
    credential_id INT REFERENCES credential(id),
    company_id INT REFERENCES company(id)
);

-- Empresa contratante, GT FOODS, SADIA E ETC... (Quem contrata o servico do VT BISCOLA)
CREATE TABLE contracting_company (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    company_id INT REFERENCES company(id)
);

-- Ter a info de quantos coletores/apanhadores a empresa tem e qual valor paga.
CREATE TABLE "collectors"(
    id serial primary key,
    quantity int not null,
    salary_value DECIMAL(10, 2),
    company_id INT REFERENCES company(id)
);

CREATE TABLE units (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    company_id INT REFERENCES company(id),
    contracting_company_id INT REFERENCES contracting_company(id)
);
CREATE TABLE team (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    default_unit_id INT REFERENCES units(id),
    company_id INT REFERENCES company(id),
    quantity_collectors int not null,
    contracting_company_id INT REFERENCES contracting_company(id)
);
CREATE TABLE  catch_type (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE daily_catch (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    quantity INT NOT NULL,
    total_cancelled INT,
    credential_id INT REFERENCES credential(id),
    units_id INT REFERENCES units(id),
    team_id INT REFERENCES team(id),
    catch_type_id INT NOT NULL REFERENCES catch_type(id),
    company_id INT REFERENCES company(id)
);
-- media de cancelamentos por equipe
-- media de cancelamentos por dia
CREATE TABLE catchs_configuration (
    id SERIAL PRIMARY KEY,
    catch_type_id INT NOT NULL REFERENCES catch_type(id),
    company_id INT REFERENCES company(id),
    catch_price DECIMAL(12, 2) NOT NULL,
    cancellation_price DECIMAL(12, 2) NOT NULL,
    UNIQUE (company_id, catch_type_id)
);
CREATE TABLE catchs_cancelled (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    credential_id INT REFERENCES credential(id),
    quantity INT NOT NULL,
    daily_catch_id INT REFERENCES daily_catch(id),
    company_id INT REFERENCES company(id),
    notes VARCHAR(100) DEFAULT 'Nao contem'
);


CREATE TABLE Vehicles (
    vehicle_id SERIAL PRIMARY KEY,
    vehicle_name VARCHAR(100) NOT NULL,
    plate_number VARCHAR(20) NOT NULL,
    unit_id INT REFERENCES units(id),
    company_id INT REFERENCES company(id)
);

CREATE TABLE Driver_Area (
    area_id SERIAL PRIMARY KEY,
    credential_id INT REFERENCES credential(id),
    vehicle_id INT REFERENCES Vehicles(vehicle_id),
    fuel INTEGER NOT NULL,
    maintenance_expenses DECIMAL(12, 2) NOT NULL,
    mileage INTEGER NOT NULL,
    daily_start_km INTEGER,
    daily_start_time TIME,
    daily_end_km INTEGER,
    daily_end_date timestamp,
    CONSTRAINT unique_user_vehicle UNIQUE (credential_id, vehicle_id),
    company_id INT REFERENCES company(id)
);

-- Table to record accounts payable
CREATE TABLE financial_accounts (
    id SERIAL PRIMARY KEY,
    description TEXT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    due_date DATE NOT NULL,
    finished_data TIMESTAMP,
    "type" int, -- 0 receber ou 1 pagar
    credential_id INT REFERENCES credential(id),
    company_id INT REFERENCES company(id)
);
-- Table to store monthly closing reports
CREATE TABLE monthly_closing_reports (
    id SERIAL PRIMARY KEY,
    month INT NOT NULL,
    year INT NOT NULL,
    total_expenses DECIMAL(12, 2) NOT NULL,
    total_income DECIMAL(12, 2) NOT NULL,
    credential_id INT REFERENCES credential(id),
    company_id INT REFERENCES company(id)
);
