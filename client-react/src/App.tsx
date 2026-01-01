import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AppProvider, useAppContext } from './context/AppContext';
import Layout from './components/Layout';
import LoginPage from './pages/LoginPage';
import Dashboard from './pages/Dashboard';
import EntityList from './pages/EntityList';
import EntityCreate from './pages/EntityCreate';
import EntityDetail from './pages/EntityDetail';
import './index.css';

const AppContent: React.FC = () => {
  const { user, isLoading } = useAppContext();

  if (isLoading) {
    return (
      <div style={{ height: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <div className="spinner">Loading...</div>
      </div>
    );
  }

  if (!user) {
    return <LoginPage />;
  }

  return (
    <Router>
      <Layout>
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/:entityType" element={<EntityList />} />
          <Route path="/:entityType/create" element={<EntityCreate />} />
          <Route path="/:entityType/view/:id" element={<EntityDetail />} />
          <Route path="*" element={<Navigate to="/" />} />
        </Routes>
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
