import type { AwardType, Project } from '../types'

const tagWeight: Record<string, number> = {
  'ระดับนานาชาติ': 5,
  'ระดับประเทศ': 4,
  'ระดับจังหวัด': 3,
  'งานในโรงเรียน': 2,
  'เข้าร่วมกิจกรรม': 1,
  'กิจกรรมเข้าร่วมมหาวิทยาลัย': 1.5,
}

export type ActivityGroup = 'university' | 'general'

const projectScope = (project: Pick<Project, 'tags'>) => Math.max(0, ...(project.tags || []).map((tag) => tagWeight[tag] || 0))

export const activityGroupForProject = (project: Pick<Project, 'tags'>): ActivityGroup | null => {
  const scope = projectScope(project)
  if (scope === 1.5) return 'university'
  if (scope === 1) return 'general'
  return null
}

const awardWeight: Record<AwardType, number> = {
  winner: 900,
  gold: 850,
  runner_up_1: 800,
  silver: 750,
  runner_up_2: 700,
  bronze: 650,
  finalist: 500,
  other: 400,
}

export const projectImportanceScore = (project: Project) => {
  const scope = projectScope(project)
  const award = project.award_type ? awardWeight[project.award_type] || 0 : 0
  const rankBoost = project.award_rank ? Math.max(0, 100 - Math.min(project.award_rank, 100)) : 0
  return scope * 10_000 + award * 10 + rankBoost
}

export const sortProjectsByImportance = (projects: Project[]) => [...projects].sort((first, second) => {
  const firstScope = projectScope(first)
  const secondScope = projectScope(second)
  if (firstScope !== secondScope) return secondScope - firstScope

  if (firstScope === 1 || firstScope === 1.5) {
    const firstOrder = first.activity_order ?? Number.POSITIVE_INFINITY
    const secondOrder = second.activity_order ?? Number.POSITIVE_INFINITY
    if (firstOrder !== secondOrder) return firstOrder - secondOrder
  }

  const scoreDifference = projectImportanceScore(second) - projectImportanceScore(first)
  if (scoreDifference) return scoreDifference
  return new Date(second.created_at).getTime() - new Date(first.created_at).getTime()
})

export const reorderActivityProjects = (projects: Project[], sourceId: number, targetId: number) => {
  const source = projects.find((project) => project.id === sourceId)
  const target = projects.find((project) => project.id === targetId)
  if (!source || !target || sourceId === targetId) return null

  const group = activityGroupForProject(source)
  if (!group || activityGroupForProject(target) !== group) return null

  const members = projects.filter((project) => activityGroupForProject(project) === group)
  const from = members.findIndex((project) => project.id === sourceId)
  const to = members.findIndex((project) => project.id === targetId)
  if (from < 0 || to < 0 || members.length > 9999) return null

  const [moved] = members.splice(from, 1)
  members.splice(to, 0, moved)
  const orderById = new Map(members.map((project, index) => [project.id, index + 1]))
  const orderedIds = members.map((project) => project.id)

  return {
    group,
    orderedIds,
    projects: sortProjectsByImportance(projects.map((project) => orderById.has(project.id)
      ? { ...project, activity_order: orderById.get(project.id)! }
      : project)),
  }
}
