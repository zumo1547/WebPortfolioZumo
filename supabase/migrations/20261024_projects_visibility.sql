begin;

create table if not exists public.site_settings (
  id text primary key check (id = 'projects'),
  maintenance boolean not null default true,
  updated_at timestamptz not null default now()
);

insert into public.site_settings(id, maintenance)
values ('projects', true)
on conflict (id) do nothing;

alter table public.site_settings enable row level security;

drop policy if exists "Anyone reads site settings" on public.site_settings;
create policy "Anyone reads site settings" on public.site_settings for select using (true);
drop policy if exists "Admins update site settings" on public.site_settings;
create policy "Admins update site settings" on public.site_settings for update using (public.is_admin()) with check (public.is_admin());

revoke all on public.site_settings from anon, authenticated;
grant select on public.site_settings to anon, authenticated;
grant update(maintenance) on public.site_settings to authenticated;

drop trigger if exists site_settings_set_updated_at on public.site_settings;
create trigger site_settings_set_updated_at before update on public.site_settings for each row execute procedure public.set_updated_at();
drop trigger if exists audit_site_settings_changes on public.site_settings;
create trigger audit_site_settings_changes after update on public.site_settings for each row execute procedure public.audit_admin_change();

drop policy if exists "Projects are public" on public.projects;
drop policy if exists "Admins read projects during maintenance" on public.projects;
drop policy if exists "Projects visible when available" on public.projects;
create policy "Projects visible when available" on public.projects for select using (
  public.is_admin() or exists (select 1 from public.site_settings where id = 'projects' and not maintenance)
);

do $$
begin
  if not exists (
    select 1 from pg_publication_tables
    where pubname = 'supabase_realtime' and schemaname = 'public' and tablename = 'site_settings'
  ) then
    alter publication supabase_realtime add table public.site_settings;
  end if;
end;
$$;

commit;
