import { lazy, Suspense, useEffect } from 'react'
import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom'
import { Layout } from './components/Layout'
import { Loading } from './components/Loading'
import { ProtectedRoute } from './components/ProtectedRoute'
import { AuthProvider } from './context/AuthContext'

const HomePage = lazy(() => import('./pages/HomePage').then((module) => ({ default: module.HomePage })))
const AboutPage = lazy(() => import('./pages/AboutPage').then((module) => ({ default: module.AboutPage })))
const ProjectsPage = lazy(() => import('./pages/ProjectsPage').then((module) => ({ default: module.ProjectsPage })))
const ContactPage = lazy(() => import('./pages/ContactPage').then((module) => ({ default: module.ContactPage })))
const AuthPage = lazy(() => import('./pages/AuthPage').then((module) => ({ default: module.AuthPage })))
const AuthCallbackPage = lazy(() => import('./pages/AuthCallbackPage').then((module) => ({ default: module.AuthCallbackPage })))
const SettingsPage = lazy(() => import('./pages/SettingsPage').then((module) => ({ default: module.SettingsPage })))
const DashboardPage = lazy(() => import('./pages/DashboardPage').then((module) => ({ default: module.DashboardPage })))
const PrivacyPage = lazy(() => import('./pages/PrivacyPage').then((module) => ({ default: module.PrivacyPage })))

function ScrollToTop() {
  const { pathname } = useLocation()
  useEffect(() => { window.scrollTo({ top: 0, behavior: 'auto' }) }, [pathname])
  return null
}

export default function App() {
  return <BrowserRouter><ScrollToTop /><AuthProvider><Layout><Suspense fallback={<Loading />}><Routes>
    <Route path="/" element={<HomePage />} />
    <Route path="/about" element={<AboutPage />} />
    <Route path="/projects" element={<ProjectsPage />} />
    <Route path="/contact" element={<ContactPage />} />
    <Route path="/privacy" element={<PrivacyPage />} />
    <Route path="/login" element={<AuthPage mode="login" />} />
    <Route path="/register" element={<AuthPage mode="register" />} />
    <Route path="/forgot-password" element={<AuthPage mode="forgot" />} />
    <Route path="/reset-password" element={<AuthPage mode="reset" />} />
    <Route path="/auth/callback" element={<AuthCallbackPage />} />
    <Route path="/settings" element={<ProtectedRoute><SettingsPage /></ProtectedRoute>} />
    <Route path="/dashboard" element={<ProtectedRoute admin><DashboardPage /></ProtectedRoute>} />
    <Route path="*" element={<HomePage />} />
  </Routes></Suspense></Layout></AuthProvider></BrowserRouter>
}
