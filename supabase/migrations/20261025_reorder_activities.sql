create or replace function public.admin_reorder_activity_projects(activity_group text, ordered_ids bigint[])
returns void
language plpgsql
security definer
set search_path = ''
as $$
declare
  expected_ids bigint[];
  supplied_ids bigint[];
begin
  if not public.is_admin() then
    raise exception 'Admin permission required' using errcode = '42501';
  end if;
  if activity_group not in ('university', 'general') or ordered_ids is null or cardinality(ordered_ids) = 0 or cardinality(ordered_ids) > 9999 then
    raise exception 'Invalid activity order' using errcode = '22023';
  end if;

  lock table public.projects in share row exclusive mode;

  select coalesce(array_agg(p.id order by p.id), array[]::bigint[])
  into expected_ids
  from public.projects p
  where not (p.tags && array['ระดับนานาชาติ', 'ระดับประเทศ', 'ระดับจังหวัด', 'งานในโรงเรียน']::text[])
    and case
      when activity_group = 'university' then 'กิจกรรมเข้าร่วมมหาวิทยาลัย' = any(p.tags)
      else 'เข้าร่วมกิจกรรม' = any(p.tags) and not 'กิจกรรมเข้าร่วมมหาวิทยาลัย' = any(p.tags)
    end;

  select coalesce(array_agg(project_id order by project_id), array[]::bigint[])
  into supplied_ids
  from unnest(ordered_ids) as supplied(project_id);

  if cardinality(ordered_ids) <> cardinality(expected_ids) or supplied_ids <> expected_ids then
    raise exception 'Activity list changed. Reload and try again.' using errcode = '22023';
  end if;

  update public.projects p
  set activity_order = ordering.position::integer
  from unnest(ordered_ids) with ordinality as ordering(project_id, position)
  where p.id = ordering.project_id;
end;
$$;

revoke all on function public.admin_reorder_activity_projects(text, bigint[]) from public;
grant execute on function public.admin_reorder_activity_projects(text, bigint[]) to authenticated;
notify pgrst, 'reload schema';
