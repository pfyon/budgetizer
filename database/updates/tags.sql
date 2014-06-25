drop table buckets;

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
