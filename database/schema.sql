CREATE TABLE accounttype (
    id integer NOT NULL,
    code character varying(20),
    description character varying(60),
    last_update timestamp without time zone DEFAULT now() NOT NULL
);

CREATE SEQUENCE accounttype_id_seq
    START WITH 1
    INCREMENT BY 1
    MINVALUE 0
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE accounttype_id_seq OWNED BY accounttype.id;

CREATE TABLE transactions (
    id integer NOT NULL,
    card_num character varying(20),
    date integer NOT NULL,
    amount integer NOT NULL,
    description character varying(160) DEFAULT ''::character varying,
    hash character varying(40),
    last_update timestamp without time zone DEFAULT now() NOT NULL,
    trantype character varying(8) NOT NULL,
    account character varying(20) NOT NULL,
    source character varying(20)
);

CREATE SEQUENCE transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    MINVALUE 0
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE transactions_id_seq OWNED BY transactions.id;

ALTER TABLE ONLY accounttype ALTER COLUMN id SET DEFAULT nextval('accounttype_id_seq'::regclass);
ALTER TABLE ONLY transactions ALTER COLUMN id SET DEFAULT nextval('transactions_id_seq'::regclass);
ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_hash_unique UNIQUE (hash);
ALTER TABLE ONLY transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);
CREATE INDEX transactions_description_index ON transactions USING btree (description);
CREATE INDEX transactions_hash_index ON transactions USING btree (hash);

create table tag_labels (
id integer not null,
label varchar(60) not null,
owner integer not null
);

create table tag_list (
id integer not null,
tag integer not null,
transaction_id integer not null
);

create sequence tag_labels_id_seq;
create sequence tag_list_id_seq;

alter table tag_list ALTER id SET DEFAULT nextval('tag_list_id_seq');
alter table tag_labels ALTER id SET DEFAULT nextval('tag_labels_id_seq');

create index tag_labels_owner_index on tag_labels (owner);
create index tag_labels_label_index on tag_labels (label);
create index tag_labels_id_index on tag_labels (id);

create index tag_list_tag_index on tag_list (tag);
create unique index tag_list_tag_transaction_id_index on tag_list (tag, transaction_id);

INSERT INTO accounttype (code, description) VALUES ('bmo', 'Bank of Montreal Debit'), ('td', 'TD Canada Debit'), ('bmo_mastercard', 'Bank of Montreal Mastercard');
