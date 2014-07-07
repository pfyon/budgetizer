create table users (
	id integer not null,
	email varchar(255) not null,
	password varchar(100) not null,
	last_login integer
);

create sequence users_id_seq;

alter table users ALTER id SET DEFAULT nextval('users_id_seq');

create index users_email_index on users (email);
create index users_id_index on users (id);

create unique index users_email_unique_index on users (email);
