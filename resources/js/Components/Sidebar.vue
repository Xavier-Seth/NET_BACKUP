<template>
    <aside class="sidebar">
      <nav class="menu">
        <ul>
          <li>
            <a href="/dashboard">
              <div class="icon-circle">
                <i class="bi bi-grid"></i>
              </div>
              <span>Dashboard</span>
            </a>
          </li>
          <li>
            <a href="/users">
              <div class="icon-circle">
                <i class="bi bi-people"></i>
              </div>
              <span>Users</span>
            </a>
          </li>
          <li>
            <a href="/documents">
              <div class="icon-circle">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <span>Documents</span>
            </a>
          </li>
          <li>
            <a href="/active-files">
              <div class="icon-circle">
                <i class="bi bi-folder2-open"></i>
              </div>
              <span>Active Files</span>
            </a>
          </li>
          <li>
            <a href="/upload">
              <div class="icon-circle">
                <i class="bi bi-upload"></i>
              </div>
              <span>Upload Files</span>
            </a>
          </li>
          <li class="exit" @click="logout">
            <div class="icon-circle exit-icon">
              <i class="bi bi-box-arrow-right"></i>
            </div>
            <span>Exit</span>
          </li>
        </ul>
      </nav>
    </aside>
  </template>
  
  <script>
  export default {
    name: "Sidebar",
    methods: {
      logout() {
        console.log("User logged out");
        // Implement logout functionality here
      }
    }
  };
  </script>
  
  <style scoped>

  /* Sidebar Styles */
  .sidebar {
    width: 220px;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    background: linear-gradient(to bottom, #12172B, #1a1f3a);
    color: white;
    padding: 10px 0;
    z-index: 1000;
}

  
  .menu {
    margin-top: 35px;
  }
  
  .menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  
  .menu li {
    margin: 10px 0;
  }
  
  .menu li a {
    text-decoration: none;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  
  .icon-circle {
    width: 45px;
    height: 45px;
    background: #2c2f48;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 3px;
  }
  
  .exit {
    margin-top: auto;
    cursor: pointer;
  }
  .exit:hover {
    opacity: 0.8;
  }
  </style>
  