<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Life International Church Admin Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #fef6e4;
    }
    .navbar {
      background-color: #8b5a2b;
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .navbar h1 {
      margin: 0;
      font-size: 22px;
    }
    .navbar a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-size: 16px;
    }
    .navbar a:hover {
      color: #f5d5a5;
    }
    .welcome-section {
      background-color: #f5d5a5;
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }
    .welcome-section h2 {
      margin: 0;
      font-size: 20px;
      color: #8b5a2b;
    }
    .welcome-section p {
      color: #5e3c1f;
      margin: 5px 0 0;
    }
    .content {
      padding: 0 20px;
    }
    .cards {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }
    .card {
      background-color: white;
      width: 300px;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    .card h3 {
      font-size: 18px;
      color: #8b5a2b;
      margin: 0 0 10px;
      text-align: center;
    }
    .card ul {
      list-style: none;
      padding: 0;
      margin: 0 0 10px;
    }
    .card ul li {
      padding: 8px 0;
      border-bottom: 1px solid #eee;
      font-size: 14px;
      color: #333;
    }
    .card ul li:last-child {
      border-bottom: none;
    }
    .card form {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .card input, .card textarea {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    .card button {
      background-color: #8b5a2b;
      color: white;
      border: none;
      padding: 8px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    .card button:hover {
      background-color: #a16a3d;
    }
    .card .delete-btn {
      background-color: #d9534f;
      margin-left: 5px;
    }
    .card .delete-btn:hover {
      background-color: #c9302c;
    }
  </style>
</head>
<body>
  <div class="navbar">
    <h1>New Life International Church Admin</h1>
    <div>
      <a href="#">Sermons</a>
      <a href="#">Events</a>
      <a href="#">Announcements</a>
      <a href="#">Visit Website</a>
    </div>
  </div>
  <div class="welcome-section">
    <h2>Welcome, Admin!</h2>
    <p>Manage the content for New Life International Church as of May 20, 2025, 11:41 AM EAT.</p>
  </div>
  <div class="content">
    <div class="cards">
      <div class="card">
        <h3>Sermons</h3>
        <ul id="sermon-list">
          <li>Faith in Action - 2025-05-18 by Pastor John</li>
          <li>Love and Compassion - 2025-05-11 by Pastor Mary</li>
        </ul>
        <form id="sermon-form">
          <input type="text" id="sermon-title" placeholder="Sermon Title" required>
          <input type="date" id="sermon-date" required>
          <input type="text" id="sermon-preacher" placeholder="Preacher" required>
          <button type="submit">Add Sermon</button>
        </form>
      </div>
      <div class="card">
        <h3>Events</h3>
        <ul id="event-list">
          <li>Community Outreach - 2025-06-01 at City Park</li>
          <li>Youth Camp - 2025-07-15 at Camp Hope</li>
        </ul>
        <form id="event-form">
          <input type="text" id="event-title" placeholder="Event Title" required>
          <input type="date" id="event-date" required>
          <input type="text" id="event-location" placeholder="Location" required>
          <button type="submit">Add Event</button>
        </form>
      </div>
      <div class="card">
        <h3>Announcements</h3>
        <ul id="announcement-list">
          <li>Join us for our annual fundraiser on June 10th! - 2025-05-20</li>
        </ul>
        <form id="announcement-form">
          <textarea id="announcement-content" placeholder="Announcement Content" required></textarea>
          <button type="submit">Add Announcement</button>
        </form>
      </div>
    </div>
  </div>
  <script>
    const sermonForm = document.getElementById('sermon-form');
    const eventForm = document.getElementById('event-form');
    const announcementForm = document.getElementById('announcement-form');
    const sermonList = document.getElementById('sermon-list');
    const eventList = document.getElementById('event-list');
    const announcementList = document.getElementById('announcement-list');

    function createDeleteButton(item, list) {
      const deleteBtn = document.createElement('button');
      deleteBtn.textContent = 'Delete';
      deleteBtn.className = 'delete-btn';
      deleteBtn.onclick = () => list.removeChild(item);
      return deleteBtn;
    }

    sermonForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const title = document.getElementById('sermon-title').value;
      const date = document.getElementById('sermon-date').value;
      const preacher = document.getElementById('sermon-preacher').value;
      const li = document.createElement('li');
      li.textContent = `${title} - ${date} by ${preacher}`;
      li.appendChild(createDeleteButton(li, sermonList));
      sermonList.appendChild(li);
      sermonForm.reset();
    });

    eventForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const title = document.getElementById('event-title').value;
      const date = document.getElementById('event-date').value;
      const location = document.getElementById('event-location').value;
      const li = document.createElement('li');
      li.textContent = `${title} - ${date} at ${location}`;
      li.appendChild(createDeleteButton(li, eventList));
      eventList.appendChild(li);
      eventForm.reset();
    });

    announcementForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const content = document.getElementById('announcement-content').value;
      const date = new Date().toISOString().split('T')[0];
      const li = document.createElement('li');
      li.textContent = `${content} - ${date}`;
      li.appendChild(createDeleteButton(li, announcementList));
      announcementList.appendChild(li);
      announcementForm.reset();
    });

    document.querySelectorAll('#sermon-list li, #event-list li, #announcement-list li').forEach(li => {
      li.appendChild(createDeleteButton(li, li.parentElement));
    });
  </script>
</body>
</html>