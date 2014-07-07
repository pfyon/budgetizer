alter table transactions add column owner integer;

update transactions set owner = 1;
alter table transactions alter column owner set not null;
