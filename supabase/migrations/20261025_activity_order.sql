alter table public.projects
  add column if not exists activity_order integer
  check (activity_order is null or activity_order between 1 and 9999);
