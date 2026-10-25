import type { AwardType, Project } from '../types'

const tagWeight: Record<string, number> = {
  'ระดับนานาชาติ': 5,
  'ระดับประเทศ': 4,
  'ระดับจังหวัด': 3,
  'งานในโรงเรียน': 2,
  'เข้าร่วมกิจกรรม': 1,
  'กิจกรรมเข้าร่วมมหาวิทยาลัย': 1.5,
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
  const scope = Math.max(0, ...(project.tags || []).map((tag) => tagWeight[tag] || 0))
  const award = project.award_type ? awardWeight[project.award_type] || 0 : 0
  const rankBoost = project.award_rank ? Math.max(0, 100 - Math.min(project.award_rank, 100)) : 0
  return scope * 10_000 + award * 10 + rankBoost
}

export const sortProjectsByImportance = (projects: Project[]) => [...projects].sort((first, second) => {
  const firstScope = Math.max(0, ...(first.tags || []).map((tag) => tagWeight[tag] || 0))
  const secondScope = Math.max(0, ...(second.tags || []).map((tag) => tagWeight[tag] || 0))
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
