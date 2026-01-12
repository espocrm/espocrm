import React, { Suspense, lazy } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AppProvider, useAppContext } from './context/AppContext';
import Layout from './components/Layout';
import './index.css';

// Lazy load pages to reduce initial bundle size
const LoginPage = lazy(() => import('./pages/LoginPage'));
const Dashboard = lazy(() => import('./pages/Dashboard'));
const EntityList = lazy(() => import('./pages/EntityList'));
const EntityCreate = lazy(() => import('./pages/EntityCreate'));
const EntityDetail = lazy(() => import('./pages/EntityDetail'));

const LoadingFallback = () => (
  <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
    <div className="spinner">Loading...</div>
  </div>
);

const AppContent: React.FC = () => {
  const { user, isLoading } = useAppContext();

  if (isLoading) {
    return <LoadingFallback />;
  }

  if (!user) {
    return (
      <Suspense fallback={<LoadingFallback />}>
        <LoginPage />
      </Suspense>
    );
  }

  return (
    <Router>
      <Layout>
        <Suspense fallback={<LoadingFallback />}>
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/:entityType" element={<EntityList />} />
            <Route path="/:entityType/create" element={<EntityCreate />} />
            <Route path="/:entityType/view/:id" element={<EntityDetail />} />
            <Route path="*" element={<Navigate to="/" />} />
          </Routes>
        </Suspense>
      </Layout>
    </Router>
  );
};

function App() {
  return (
    <AppProvider>
      <AppContent />
    </AppProvider>
  );
}

export default App;
