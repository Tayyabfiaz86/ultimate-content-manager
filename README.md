# 📊 UCM Plugin – Universal Content Manager (Surveys & Tests)

**UCM Plugin** is a WordPress plugin that allows you to easily **create, manage, and analyze Surveys & Tests**.  
It’s designed for **teachers, trainers, HR teams, and researchers** who need a simple yet professional tool.

---

## 🚀 Plugin Pages / Flows

### 1. Content Manager (Create Page)
- Create new Survey/Test
- Options:
  - Type: Survey / Test
  - Survey Type: Question & Answer / Multiple Choice
  - Test Type: Multiple Choice (+ correct answer selection)
- Users can add multiple questions

---

### 2. Survey/Test Details (Listing Page)
- Lists all created Surveys/Tests
- Filters:
  - Type (Survey/Test)
  - Sub-type (Q&A / Multiple Choice)

---

### 3. Test Choices Results (Results Page)
- Table includes: Name, Email, Correct, Wrong, Score, Action (View)
- Filters:
  - Select Test
  - Include Passed / Include Failed
- **View**: Detailed answers with correct/incorrect highlights
- Option to print **Pass/Fail student lists**

---

### 4. Survey Choices Results
- Table includes: Survey ID, Title, Number of Responses, Actions (View, Add Summary)
- **View**: Shows MCQ response counts (per choice, with totals)
- **Add Summary**: Enter custom analysis/summary (saved in DB)

---

### 5. Test Reports Page
- Table includes: Test ID, User Name, Email, Correct, Incorrect, Unchecked, Total Questions, Attempts, Action (View)
- **View**: Open student’s test → manually mark correct/incorrect if needed
- Reports:
  - Generate Reports (Test-wise / Student-wise)
  - Download Excel (summary sheet + individual student sheets)

---

### 6. Survey Q&A Results
- Table includes: Survey ID, Name, Total Questions, Attempted By, Actions (View, Add Summary)
- **View**: Displays all questions + all users’ answers (name, email, answer under each question)
- **Add Summary**: Save analysis/summary for survey
- **Excel Report**:
  - Sheet 1 → Summary
  - Sheet 2 → Detailed answers (per question with user/email/answer)

---

## 🔑 Key Features
- Support for both **Surveys & Tests**
- Multiple question types (MCQ & short answer)
- Admin dashboard with filters & listings
- Detailed result views (per student / per survey)
- Manual + automatic evaluation for tests
- Excel exports (Survey-wise & Test-wise)
- Custom summaries/analysis stored in DB
- Pass/Fail reports & printable student lists

---

## 📦 Installation
1. Download the plugin ZIP from [Releases](#).
2. In your WordPress admin panel → Plugins → Add New → Upload Plugin.
3. Upload the ZIP and click **Activate**.
4. Done! 🎉

---

## 🔧 Usage
- Go to **UCM → Content Manager** to create new Surveys or Tests.
- Add questions (MCQ / Q&A).
- Use shortcodes to display them on any page/post.
- Track results in **Results / Reports Pages**.
- Export reports to Excel for deeper analysis.

---

## 📈 Roadmap
- ✅ Core plugin pages & reports
- ⏳ More Export Formats (Google Sheets Integration)
- ⏳ Frontend Student Dashboard
- ⏳ AI-powered Auto Summaries

---

## 🤝 Contributing
Pull requests are welcome! For major changes, please open an issue first to discuss what you’d like to change.

---

## 📜 License
[GPL v2 or later](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

---

## 👨‍💻 Author
Developed with ❤️ by Tayyab Fiaz
