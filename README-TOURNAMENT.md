# 🎯 Pinball Tournament Team Tracker

A Laravel 12 + React application for tournament directors to track team performance during pinball matchplay events. Features real-time leaderboards, QR code access for players, and seamless integration with the Matchplay Events API.

## ✨ Features

### For Tournament Directors
- **Tournament Management**: Create and sync tournaments from Matchplay Events
- **Team Creation**: Pair players with auto-generated funny pinball-themed names
- **Real-time Sync**: Pull live data from Matchplay API including standings and rounds
- **QR Code Generation**: Create shareable leaderboards for players
- **Dark Mode UI**: Touch-optimized interface perfect for iPad use

### For Players
- **QR Code Access**: Scan codes to view live leaderboards
- **Auto-refresh**: Real-time updates without manual refresh
- **Mobile Optimized**: Responsive design for phones and tablets
- **Team Standings**: Combined scores from both team members

## 🚀 Quick Start

### Prerequisites
- PHP 8.4+
- Node.js 18+
- Laravel 12
- Matchplay Events API token

### Installation

1. **Clone and Install**
   ```bash
   git clone https://github.com/200even/pinball-tourney-teams.git
   cd pinball-tourney-teams
   composer install
   npm install
   ```

2. **Setup Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure Database**
   ```bash
   # Update .env with your database settings
   php artisan migrate
   ```

4. **Build Assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

## 🔧 Configuration

### Matchplay API Setup

1. **Get API Token**
   - Visit [matchplay.events](https://app.matchplay.events)
   - Go to Account Settings → API Tokens
   - Generate a new token

2. **Add Token to Profile**
   - Register/login to your tournament tracker
   - Go to Settings → Profile
   - Add your Matchplay API token
   - Save settings

## 📱 Usage Guide

### Creating a Tournament

1. **Navigate to Tournaments**
   - Click "Create Tournament" 
   - Enter Matchplay Tournament ID (found in URL)
   - Add tournament name and description
   - Click "Create Tournament"

2. **Import Players**
   - Tournament automatically imports players from Matchplay
   - Players are cached locally for performance

### Managing Teams

1. **Create Teams**
   - Go to tournament page
   - Click "Add Team" in Teams section
   - Select two players from dropdown
   - Optionally customize team name
   - Save team

2. **Team Names**
   - Auto-generated funny pinball names (e.g., "Flippin' Bumpers Brigade")
   - Fully customizable
   - Regenerate new names anytime

### Live Leaderboard

1. **Generate QR Code**
   - Click "QR Code" button on tournament page
   - Download or display QR code
   - Players scan to access leaderboard

2. **Real-time Updates**
   - Click "Sync Data" to pull latest from Matchplay
   - Leaderboard auto-refreshes every 30 seconds
   - Combined team scores from both players

## 🎮 API Integration

### Supported Endpoints
- `GET /api/tournaments/{id}` - Tournament details
- `GET /api/tournaments/{id}/standings` - Player standings  
- `GET /api/tournaments/{id}/rounds` - Tournament rounds
- `GET /api/profiles/{id}` - Player profiles
- `GET /api/dashboard` - User tournaments

### Rate Limiting
- Respects Matchplay API rate limits
- Caches data locally to minimize API calls
- Error handling with fallback options

## 🎨 Design Features

### Dark Mode Default
- App defaults to dark mode (perfect for tournament venues)
- Light mode toggle available
- System preference detection

### iPad Optimized
- Touch-first design with 44px minimum tap targets
- Smooth scrolling and animations
- No zoom on double-tap
- Optimized spacing for tablet use

## 🧪 Testing

### API Integration Test
```bash
# Test API connection
php artisan matchplay:test {user_id} {tournament_id}

# Example
php artisan matchplay:test 1 12345
```

### Run Application Tests
```bash
php artisan test
```

## 📂 Project Structure

```
app/
├── Http/Controllers/
│   ├── TournamentController.php    # Tournament CRUD
│   ├── TeamController.php          # Team management
│   └── LeaderboardController.php   # Public leaderboard
├── Models/
│   ├── Tournament.php              # Tournament model
│   ├── Team.php                    # Team model
│   ├── Player.php                  # Player model
│   └── Round.php                   # Round model
└── Services/
    ├── MatchplayApiService.php     # API integration
    └── TeamNameGeneratorService.php # Name generation

resources/js/
├── pages/
│   ├── tournaments/               # Tournament pages
│   └── leaderboard/              # Public leaderboard
└── components/
    ├── team-management.tsx        # Team interface
    └── qr-code-display.tsx       # QR code modal
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🎯 Tournament Director Tips

### Best Practices
- **Pre-tournament**: Set up tournament and test QR codes
- **During tournament**: Sync data after each round
- **Display setup**: Use iPad/tablet for easy team management
- **Player access**: Post QR codes prominently for easy scanning

### Troubleshooting
- **API errors**: Check token validity in profile settings
- **Missing players**: Ensure tournament has started on Matchplay
- **Sync issues**: Verify tournament ID and API connectivity

---

Built with ❤️ for the pinball community using Laravel 12, React, and the Matchplay Events API.
