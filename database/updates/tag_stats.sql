create table tag_stats (
	id integer not null,
	user_id integer not null,
	tag_label_id integer not null
);

create sequence tag_stats_id_seq;

alter table tag_stats ALTER id SET DEFAULT nextval('tag_stats_id_seq');

create index tag_stats_user_id_index on tag_stats (user_id);

create unique index tag_stats_user_id_tag_label_id_index on tag_stats (user_id, tag_label_id);
