# Git Workflow Guide: `dev` & `main` Branches

This guide explains the Git branching workflow for **Business Analytics Dashboard**, connected to remote repository:
`https://github.com/ChamiPallewatte/business-analytics-dashboard.git`

---

## 📌 Branch Strategy

* **`dev` Branch**: Active development & local testing. All daily code changes, new features, and bug fixes happen here.
* **`main` Branch**: Production-ready codebase. Code is merged into `main` ONLY after features are thoroughly tested on `dev`.

---

## 🔄 1. Daily Development Workflow (`dev` Branch)

Whenever you edit files or build new features, follow these steps:

### Step 1: Ensure you are on the `dev` branch
```bash
git checkout dev
```

### Step 2: Pull latest changes (Sync with GitHub)
```bash
git pull origin dev
```

### Step 3: Make your code changes and test locally
Edit your code files, test them locally in your workspace/browser.

### Step 4: Check modified files
```bash
git status
```

### Step 5: Stage modified files
To stage all changes:
```bash
git add .
```
*(Or stage specific files: `git add filename.php`)*

### Step 6: Commit your changes
Write a clear, descriptive message explaining what you changed:
```bash
git commit -m "Add new feature or fix description"
```

### Step 7: Push changes to GitHub (`dev` branch)
```bash
git push origin dev
```

---

## 🚀 2. Merging to `main` (Final / Release Phase)

When all features on `dev` are complete and tested, merge them into `main`:

### Step 1: Commit and push all pending work on `dev`
```bash
git checkout dev
git status
# (Make sure working tree is clean)
git push origin dev
```

### Step 2: Switch to the `main` branch
```bash
git checkout main
```

### Step 3: Pull latest changes on `main`
```bash
git pull origin main
```

### Step 4: Merge `dev` into `main`
```bash
git merge dev
```

### Step 5: Push updated `main` branch to GitHub
```bash
git push origin main
```

### Step 6: Switch back to `dev` branch for future work
```bash
git checkout dev
```

---

## ⚡ Quick Reference Commands Cheat Sheet

| Action | Command |
| :--- | :--- |
| **Check current branch & status** | `git status` |
| **Switch to dev branch** | `git checkout dev` |
| **Switch to main branch** | `git checkout main` |
| **Pull latest code from dev** | `git pull origin dev` |
| **Stage all changes** | `git add .` |
| **Commit staged changes** | `git commit -m "Your message"` |
| **Push code to dev** | `git push origin dev` |
| **Push code to main** | `git push origin main` |
| **See commit history** | `git log --oneline -n 10` |

---

> ℹ️ **Note on Deployment**: Hosting / deployment to Hostinger will be handled later after project completion. Right now, all work remains inside Git using this `dev` and `main` strategy.
