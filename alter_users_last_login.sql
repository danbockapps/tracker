alter table wrc_users add last_login datetime;
alter table wrc_users add last_message_from datetime;
alter table wrc_users add last_message_to datetime;

update wrc_users u
	left join (
		select
			user_id,
			max(pv_dttm) as pv_dttm
		from wrc_pageviews
		group by user_id
	) p
	on u.user_id = p.user_id
	set last_login = pv_dttm;

update wrc_users u
	left join (
		select
			user_id,
			max(create_dttm) as create_dttm
		from wrc_messages
		group by user_id
	) m
	on u.user_id = m.user_id
	set last_message_from = create_dttm;

update wrc_users u
	left join (
		select
			recip_id,
			max(create_dttm) as create_dttm
		from wrc_messages
		group by recip_id
	) m
	on u.user_id = m.recip_id
	set last_message_to = create_dttm;
