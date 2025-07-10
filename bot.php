<?php
// config.php - Konfiguratsiya
class Config {
    const BOT_TOKEN = 'YOUR_BOT_TOKEN_HERE';
    const CHANNEL_ID = '@your_channel_username';
    const ADMIN_ID = 123456789;
    const WEBHOOK_URL = 'https://yourserver.com/webhook.php';
    const DB_PATH = 'bot_database.db';
}

// database.php - To'liq ma'lumotlar bazasi boshqaruvi
class Database {
    private $pdo;
    private $categories;
    
    public function __construct() {
        $this->categories = [
            'tibbiyot' => [
                'name' => '🏥 Tibbiyot',
                'description' => 'Shifokorlar, klinikalar, dorilar',
                'icon' => '🏥'
            ],
            'talim' => [
                'name' => '📚 Ta\'lim',
                'description' => 'Maktablar, kurslar, darslar',
                'icon' => '📚'
            ],
            'biznes' => [
                'name' => '💼 Biznes',
                'description' => 'Biznes takliflar, hamkorlik',
                'icon' => '💼'
            ],
            'kochmas_mulk' => [
                'name' => '🏠 Ko\'chmas mulk',
                'description' => 'Uy, kvartira, ofis',
                'icon' => '🏠'
            ],
            'transport' => [
                'name' => '🚗 Transport',
                'description' => 'Avtomobil, taksi, yuk',
                'icon' => '🚗'
            ],
            'xarid_sotiq' => [
                'name' => '🛍️ Xarid-sotiq',
                'description' => 'Tovarlar, savdo',
                'icon' => '🛍️'
            ],
            'xizmatlar' => [
                'name' => '🔧 Xizmatlar',
                'description' => 'Ta\'mirlash, xizmatlar',
                'icon' => '🔧'
            ],
            'texnologiya' => [
                'name' => '📱 Texnologiya',
                'description' => 'Telefon, kompyuter, IT',
                'icon' => '📱'
            ],
            'oziq_ovqat' => [
                'name' => '🍕 Oziq-ovqat',
                'description' => 'Restoran, cafe, yetkazib berish',
                'icon' => '🍕'
            ],
            'sport' => [
                'name' => '⚽ Sport',
                'description' => 'Fitnes, sport klublari',
                'icon' => '⚽'
            ]
        ];
        
        $this->connectDatabase();
        $this->createTables();
        $this->insertDefaultData();
    }
    
    private function connectDatabase() {
        try {
            $this->pdo = new PDO('sqlite:' . Config::DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        $queries = [
            // Foydalanuvchilar jadvali
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER UNIQUE NOT NULL,
                username TEXT,
                first_name TEXT,
                last_name TEXT,
                phone TEXT,
                category TEXT,
                step TEXT DEFAULT 'start',
                language TEXT DEFAULT 'uz',
                is_banned INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Postlar jadvali
            "CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                category TEXT NOT NULL,
                title TEXT,
                content TEXT NOT NULL,
                phone TEXT,
                price TEXT,
                location TEXT,
                status TEXT DEFAULT 'pending',
                admin_comment TEXT,
                views INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id)
            )",
            
            // Kategoriyalar jadvali
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                description TEXT,
                icon TEXT,
                is_active INTEGER DEFAULT 1,
                order_index INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Admin harakatlari logi
            "CREATE TABLE IF NOT EXISTS admin_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                target_type TEXT,
                target_id INTEGER,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Statistika jadvali
            "CREATE TABLE IF NOT EXISTS statistics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date DATE NOT NULL,
                total_users INTEGER DEFAULT 0,
                new_users INTEGER DEFAULT 0,
                total_posts INTEGER DEFAULT 0,
                approved_posts INTEGER DEFAULT 0,
                rejected_posts INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($queries as $query) {
            $this->pdo->exec($query);
        }
        
        // Indekslar yaratish
        $this->createIndexes();
    }
    
    private function createIndexes() {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_users_user_id ON users(user_id)",
            "CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status)",
            "CREATE INDEX IF NOT EXISTS idx_posts_category ON posts(category)",
            "CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id)"
        ];
        
        foreach ($indexes as $index) {
            $this->pdo->exec($index);
        }
    }
    
    private function insertDefaultData() {
        // Kategoriyalarni qo'shish
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM categories");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $stmt = $this->pdo->prepare("INSERT INTO categories (code, name, description, icon, order_index) VALUES (?, ?, ?, ?, ?)");
            $order = 1;
            foreach ($this->categories as $code => $data) {
                $stmt->execute([$code, $data['name'], $data['description'], $data['icon'], $order]);
                $order++;
            }
        }
    }
    
    // Foydalanuvchi operatsiyalari
    public function saveUser($userData) {
        $sql = "INSERT OR REPLACE INTO users (user_id, username, first_name, last_name, updated_at) VALUES (?, ?, ?, ?, datetime('now'))";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $userData['id'],
            $userData['username'] ?? null,
            $userData['first_name'] ?? null,
            $userData['last_name'] ?? null
        ]);
    }
    
    public function getUser($userId) {
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function updateUserStep($userId, $step) {
         $sql = "UPDATE users SET step = ?, updated_at = datetime('now') WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$step, $userId]);
    }
    
    public function updateUserCategory($userId, $category) {
        $sql = "UPDATE users SET category = ?, updated_at = datetime('now') WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$category, $userId]);
    }
    
    public function updateUserPhone($userId, $phone) {
        $sql = "UPDATE users SET phone = ?, updated_at = datetime('now') WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$phone, $userId]);
    }
    
    public function banUser($userId, $isBanned = 1) {
        $sql = "UPDATE users SET is_banned = ?, updated_at = datetime('now') WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$isBanned, $userId]);
    }
    
    // Post operatsiyalari
    public function savePost($postData) {
        $sql = "INSERT INTO posts (user_id, category, title, content, phone, price, location) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $postData['user_id'],
            $postData['category'],
            $postData['title'] ?? null,
            $postData['content'],
            $postData['phone'] ?? null,
            $postData['price'] ?? null,
            $postData['location'] ?? null
        ]);
    }
    
    public function getPost($postId) {
        $sql = "SELECT p.*, u.username, u.first_name, c.name as category_name, c.icon as category_icon 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id 
                JOIN categories c ON p.category = c.code 
                WHERE p.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$postId]);
        return $stmt->fetch();
    }
    
    public function getPendingPosts($limit = 1, $offset = 0) {
        $sql = "SELECT p.*, u.username, u.first_name, c.name as category_name, c.icon as category_icon 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id 
                JOIN categories c ON p.category = c.code 
                WHERE p.status = 'pending' 
                ORDER BY p.created_at ASC 
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function updatePostStatus($postId, $status, $adminComment = null) {
        $sql = "UPDATE posts SET status = ?, admin_comment = ?, updated_at = datetime('now') WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $adminComment, $postId]);
    }
    
    public function incrementPostViews($postId) {
        $sql = "UPDATE posts SET views = views + 1 WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$postId]);
    }
    
    // Kategoriya operatsiyalari
    public function getCategories($activeOnly = true) {
        $sql = "SELECT * FROM categories";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY order_index ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getCategoryByCode($code) {
        $sql = "SELECT * FROM categories WHERE code = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    // Statistika operatsiyalari
    public function countPendingPosts() {
        $sql = "SELECT COUNT(*) FROM posts WHERE status = 'pending'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function countTotalUsers() {
        $sql = "SELECT COUNT(*) FROM users";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function countActiveUsers($days = 7) {
        $sql = "SELECT COUNT(*) FROM users WHERE updated_at >= datetime('now', '-{$days} days')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getPostsByCategory() {
        $sql = "SELECT c.name, c.icon, COUNT(p.id) as count 
                FROM categories c 
                LEFT JOIN posts p ON c.code = p.category 
                GROUP BY c.code 
                ORDER BY count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getPostsByStatus() {
        $sql = "SELECT status, COUNT(*) as count FROM posts GROUP BY status";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDailyStats($days = 30) {
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM posts 
                WHERE created_at >= datetime('now', '-{$days} days') 
                GROUP BY DATE(created_at) 
                ORDER BY date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getTopUsers($limit = 10) {
        $sql = "SELECT u.user_id, u.username, u.first_name, COUNT(p.id) as post_count 
                FROM users u 
                LEFT JOIN posts p ON u.user_id = p.user_id 
                GROUP BY u.user_id 
                ORDER BY post_count DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // Admin log operatsiyalari
    public function logAdminAction($adminId, $action, $targetType = null, $targetId = null, $details = null) {
        $sql = "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$adminId, $action, $targetType, $targetId, $details]);
    }
    
    public function getAdminLogs($limit = 50) {
        $sql = "SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // Qidiruv operatsiyalari
    public function searchPosts($query, $status = null, $category = null, $limit = 20) {
        $sql = "SELECT p.*, u.username, u.first_name, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id 
                JOIN categories c ON p.category = c.code 
                WHERE (p.content LIKE ? OR p.title LIKE ?)";
        
        $params = ["%{$query}%", "%{$query}%"];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        if ($category) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function searchUsers($query, $limit = 20) {
        $sql = "SELECT * FROM users 
                WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? 
                ORDER BY updated_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["%{$query}%", "%{$query}%", "%{$query}%", $limit]);
        return $stmt->fetchAll();
    }
    
    // Backup va maintenance
    public function backupDatabase() {
        $backup = [
            'users' => [],
            'posts' => [],
            'categories' => [],
            'admin_logs' => [],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $tables = ['users', 'posts', 'categories', 'admin_logs'];
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->prepare("SELECT * FROM {$table}");
            $stmt->execute();
            $backup[$table] = $stmt->fetchAll();
        }
        
        return json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    public function cleanOldData($days = 30) {
        $sql = "DELETE FROM admin_logs WHERE created_at < datetime('now', '-{$days} days')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute();
    }
    
    public function vacuumDatabase() {
        return $this->pdo->exec("VACUUM");
    }
    
    // Utility metodlar
    public function getUserPostsCount($userId) {
        $sql = "SELECT COUNT(*) FROM posts WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    public function getUserApprovedPostsCount($userId) {
        $sql = "SELECT COUNT(*) FROM posts WHERE user_id = ? AND status = 'approved'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    public function isUserBanned($userId) {
        $sql = "SELECT is_banned FROM users WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetchColumn();
        return $result === 1;
    }
    
    public function getRecentPosts($limit = 10) {
        $sql = "SELECT p.*, u.username, u.first_name, c.name as category_name, c.icon as category_icon 
                FROM posts p 
                JOIN users u ON p.user_id = u.user_id 
                JOIN categories c ON p.category = c.code 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

// telegram.php - Telegram API
class TelegramBot {
    private $botToken;
    private $apiUrl;
    
    public function __construct() {
        $this->botToken = Config::BOT_TOKEN;
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }
    
    public function sendMessage($chatId, $text, $replyMarkup = null, $parseMode = 'HTML') {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->makeRequest('editMessageText', $data);
    }
    
    public function deleteMessage($chatId, $messageId) {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId
        ];
        
        return $this->makeRequest('deleteMessage', $data);
    }
    
    public function answerCallbackQuery($callbackQueryId, $text = null, $showAlert = false) {
        $data = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert
        ];
        
        if ($text) {
            $data['text'] = $text;
        }
        
        return $this->makeRequest('answerCallbackQuery', $data);
    }
    
    public function setWebhook($url) {
        return $this->makeRequest('setWebhook', ['url' => $url]);
    }
    
    public function getMe() {
        return $this->makeRequest('getMe');
    }
    
    public function getChatMembersCount($chatId) {
        return $this->makeRequest('getChatMembersCount', ['chat_id' => $chatId]);
    }
    
    // Barcha foydalanuvchilarga xabar yuborish
    public function broadcastMessage($userIds, $text, $replyMarkup = null) {
        $results = [];
        foreach ($userIds as $userId) {
            $result = $this->sendMessage($userId, $text, $replyMarkup);
            $results[] = [
                'user_id' => $userId,
                'success' => $result && $result['ok'],
                'error' => $result['description'] ?? null
            ];
            
            // API limit uchun kichik kutish
            usleep(100000); // 0.1 sekund
        }
        return $results;
    }
    
    private function makeRequest($method, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['ok' => false, 'description' => 'HTTP Error: ' . $httpCode];
        }
        
        return json_decode($response, true);
    }
}

// bot.php - Asosiy bot logic
class Bot {
    private $db;
    private $telegram;
    private $adminCommands;
    
    public function __construct() {
        $this->db = new Database();
        $this->telegram = new TelegramBot();
        $this->adminCommands = [
            '/admin' => 'Admin panel',
            '/stats' => 'Statistika',
            '/broadcast' => 'Barcha foydalanuvchilarga xabar',
            '/posts' => 'Postlar boshqaruvi',
            '/users' => 'Foydalanuvchilar',
            '/categories' => 'Kategoriyalar',
            '/backup' => 'Backup yaratish',
            '/logs' => 'Admin loglari'
        ];
    }
    
    public function handleUpdate($update) {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }
    
    private function handleMessage($message) {
        $userId = $message['from']['id'];
        $username = $message['from']['username'] ?? '';
        $firstName = $message['from']['first_name'] ?? '';
        $lastName = $message['from']['last_name'] ?? '';
        $text = $message['text'] ?? '';
        
        // Foydalanuvchini bazaga saqlash
        $this->db->saveUser($message['from']);
        $user = $this->db->getUser($userId);
        
        // Banned foydalanuvchilarni tekshirish
        if ($this->db->isUserBanned($userId)) {
            $this->telegram->sendMessage($userId, "⛔ Sizning hisobingiz bloklangan. Admin bilan bog'laning.");
            return;
        }
        
        // Admin komandalarini tekshirish
        if ($userId == Config::ADMIN_ID) {
            if (isset($this->adminCommands[$text])) {
                $this->handleAdminCommand($userId, $text);
                return;
            }
            
            // Broadcast jarayonini tekshirish
            if ($user['step'] === 'broadcast_waiting') {
                $this->processBroadcast($userId, $text);
                return;
            }
        }
        
        // Telefon raqam ulashish
        if (isset($message['contact'])) {
            $phone = $message['contact']['phone_number'];
            $this->db->updateUserPhone($userId, $phone);
            $this->db->updateUserStep($userId, 'phone_received');
            
            $this->telegram->sendMessage($userId, "📱 Telefon raqamingiz qabul qilindi!\n\nEndi postingizni yozing:", $this->getBackToMenuKeyboard());
            return;
        }
        
        // Boshlang'ich komandalar
        if ($text === '/start') {
            $this->showWelcome($userId);
            return;
        }
        
        if ($text === '/help') {
            $this->showHelp($userId);
            return;
        }
        
        if ($text === '/myprofile') {
            $this->showUserProfile($userId);
            return;
        }
        
        // Foydalanuvchi qadamlariga qarab javob berish
        switch ($user['step']) {
            case 'start':
                $this->showWelcome($userId);
                break;
                
            case 'category_selected':
                if ($user['phone']) {
                    $this->saveUserPost($userId, $text);
                } else {
                    $this->requestPhone($userId);
                }
                break;
                
            case 'phone_received':
                $this->saveUserPost($userId, $text);
                break;
                
            default:
                $this->showWelcome($userId);
                break;
        }
    }
    
    private function handleCallbackQuery($callbackQuery) {
        $userId = $callbackQuery['from']['id'];
        $messageId = $callbackQuery['message']['message_id'];
        $data = $callbackQuery['data'];
        
        // Callback javob berish
        $this->telegram->answerCallbackQuery($callbackQuery['id']);
        
        // Admin panel callback'lari
        if ($userId == Config::ADMIN_ID && strpos($data, 'admin_') === 0) {
            $this->handleAdminCallback($userId, $messageId, $data);
            return;
        }
        
        // Kategoriya tanlash
        if (strpos($data, 'category_') === 0) {
            $category = str_replace('category_', '', $data);
            $this->selectCategory($userId, $category);
        }
        
        // Orqaga qaytish
        if ($data === 'back_to_menu') {
            $this->showWelcome($userId);
        }
    }
    
    private function handleAdminCommand($userId, $command) {
        switch ($command) {
            case '/admin':
                $this->showAdminPanel($userId);
                break;
                
            case '/stats':
                $this->showStatistics($userId);
                break;
                
            case '/broadcast':
                $this->startBroadcast($userId);
                break;
                
            case '/posts':
                $this->showPostsManagement($userId);
                break;
                
            case '/users':
                $this->showUsersManagement($userId);
                break;
                
            case '/categories':
                $this->showCategoriesManagement($userId);
                break;
                
            case '/backup':
                $this->createBackup($userId);
                break;
                
            case '/logs':
                $this->showAdminLogs($userId);
                break;
        }
    }
    
    private function showWelcome($userId) {
        $categories = $this->db->getCategories();
        $keyboard = ['inline_keyboard' => []];
        
        // Kategoriyalarni 2 tadan qilib joylashtirish
        $row = [];
        foreach ($categories as $i => $category) {
            $row[] = [
                'text' => $category['icon'] . ' ' . $category['name'],
                'callback_data' => 'category_' . $category['code']
            ];
            
            if (($i + 1) % 2 == 0 || $i == count($categories) - 1) {
                $keyboard['inline_keyboard'][] = $row;
                $row = [];
            }
        }
        
        // Qo'shimcha tugmalar
        $keyboard['inline_keyboard'][] = [
            ['text' => '👤 Mening profilim', 'callback_data' => 'my_profile'],
            ['text' => '📊 Statistika', 'callback_data' => 'bot_stats']
        ];
        
        $text = "🎯 <b>Xush kelibsiz!</b>\n\n";
        $text .= "Bu bot orqali turli xil e'lonlar berish mumkin.\n\n";
        $text .= "🔹 Quyidagi yo'nalishlardan birini tanlang:\n";
        $text .= "🔹 Postingizni yozing\n";
        $text .= "🔹 Telefon raqamingizni ulashing\n";
        $text .= "🔹 Admin tasdiqlashini kuting\n\n";
        $text .= "📢 Tasdiqlangan postlar kanalda e'lon qilinadi!";
        
        $this->telegram->sendMessage($userId, $text, $keyboard);
        $this->db->updateUserStep($userId, 'start');
    }
    
    private function showHelp($userId) {
        $text = "📋 <b>Yordam</b>\n\n";
        $text .= "🔹 <b>/start</b> - Botni qayta ishga tushirish\n";
        $text .= "🔹 <b>/help</b> - Yordam\n";
        $text .= "🔹 <b>/myprofile</b> - Mening profilim\n\n";
        $text .= "📝 <b>Qanday foydalanish:</b>\n";
        $text .= "1️⃣ Kategoriyani tanlang\n";
        $text .= "2️⃣ Postingizni yozing\n";
        $text .= "3️⃣ Telefon raqamingizni ulashing\n";
        $text .= "4️⃣ Admin tasdiqlashini kuting\n\n";
        $text .= "💡 <b>Maslahat:</b> Post aniq va batafsil bo'lsin!";
        
        $this->telegram->sendMessage($userId, $text, $this->getBackToMenuKeyboard());
    }
    
    private function showUserProfile($userId) {
        $user = $this->db->getUser($userId);
        $totalPosts = $this->db->getUserPostsCount($userId);
        $approvedPosts = $this->db->getUserApprovedPostsCount($userId);
        
        $text = "👤 <b>Mening profilim</b>\n\n";
        $text .= "🆔 <b>ID:</b> {$user['user_id']}\n";
        $text .= "👤 <b>Ism:</b> {$user['first_name']}\n";
        if ($user['username']) {
            $text .= "📝 <b>Username:</b> @{$user['username']}\n";
        }
        if ($user['phone']) {
            $text .= "📞 <b>Telefon:</b> {$user['phone']}\n";
        }
        $text .= "📅 <b>Ro'yxatdan o'tgan:</b> " . date('d.m.Y H:i', strtotime($user['created_at'])) . "\n\n";
        
        $text .= "📊 <b>Statistika:</b>\n";
        $text .= "📝 Jami postlar: {$totalPosts}\n";
        $text .= "✅ Tasdiqlangan: {$approvedPosts}\n";
        $text .= "⏳ Kutayotgan: " . ($totalPosts - $approvedPosts) . "\n";
        
        $this->telegram->sendMessage($userId, $text, $this->getBackToMenuKeyboard());
    }
    
    private function showStatistics($userId) {
        $totalUsers = $this->db->countTotalUsers();
        $activeUsers = $this->db->countActiveUsers(7);
        $pendingPosts = $this->db->countPendingPosts();
        $postsByStatus = $this->db->getPostsByStatus();
        $postsByCategory = $this->db->getPostsByCategory();
        
        $text = "📊 <b>Bot Statistikasi</b>\n\n";
        $text .= "👥 <b>Foydalanuvchilar:</b>\n";
        $text .= "• Jami: {$totalUsers}\n";
        $text .= "• Faol (7 kun): {$activeUsers}\n\n";
        
        $text .= "📝 <b>Postlar holati:</b>\n";
        foreach ($postsByStatus as $status) {
            $statusName = [
                'pending' => '⏳ Kutayotgan',
                'approved' => '✅ Tasdiqlangan',
                'rejected' => '❌ Rad etilgan'
            ][$status['status']] ?? $status['status'];
            $text .= "• {$statusName}: {$status['count']}\n";
        }
        
        $text .= "\n📂 <b>Kategoriyalar bo'yicha:</b>\n";
        foreach ($postsByCategory as $category) {
            if ($category['count'] > 0) {
                $text .= "• {$category['icon']} {$category['name']}: {$category['count']}\n";
            }
        }
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔄 Yangilash', 'callback_data' => 'admin_refresh_stats'],
                    ['text' => '📈 Batafsil', 'callback_data' => 'admin_detailed_stats']
                ]
            ]
        ];
        
        $this->telegram->sendMessage($userId, $text, $keyboard);
        $this->db->logAdminAction($userId, 'view_statistics');
    }
    
    private function startBroadcast($userId) {
        $this->db->updateUserStep($userId, 'broadcast_waiting');
        
        $text = "📢 <b>Barcha foydalanuvchilarga xabar yuborish</b>\n\n";
        $text .= "Yubormoqchi bo'lgan xabaringizni yozing:\n\n";
        $text .= "⚠️ <b>Diqqat:</b> Xabar barcha foydalanuvchilarga yuboriladi!";
        
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '❌ Bekor qilish', 'callback_data' => 'admin_cancel_broadcast']]
            ]
        ];
        
        $this->telegram->sendMessage($userId, $text, $keyboard);
    }
    
    private function processBroadcast($userId, $message) {
        $this->db->updateUserStep($userId, 'start');
        
        // Barcha foydalanuvchilarni olish
        $stmt = $this->db->pdo->prepare("SELECT user_id FROM users WHERE is_banned = 0");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $text = "📢 <b>Xabar barcha foydalanuvchilarga yuborilmoqda...</b>\n\n";
        $text .= "Jami foydalanuvchilar: " . count($users) . "\n";
        $text .= "Yuborilayotgan xabar:\n\n";
        $text .= "--- --- ---\n";
        $text .= $message . "\n";
        $text .= "--- --- ---";
        
        $this->telegram->sendMessage($userId, $text);
        
        // Broadcast xabar
        $broadcastMessage = "📢 <b>Admin xabari</b>\n\n" . $message;
        $results = $this->telegram->broadcastMessage($users, $broadcastMessage);
        
        // Natijalarni hisoblash
        $success = count(array_filter($results, function($r) { return $r['success']; }));
        $failed = count($results) - $success;
        
        $resultText = "✅ <b>Broadcast yakunlandi!</b>\n\n";
        $resultText .= "📊 Natijalar:\n";
        $resultText .= "• Muvaffaqiyatli: {$success}\n";
        $resultText .= "• Xatolik: {$failed}\n";
        $resultText .= "• Jami: " . count($results);
        
        $this->telegram->sendMessage($userId, $resultText);
        $this->db->logAdminAction($userId, 'broadcast_message', 'all_users', null, "Sent to {$success} users");
    }
    
    private function showPostsManagement($userId, $offset = 0) {
        $posts = $this->db->getPendingPosts(1, $offset);
        $totalPosts = $this->db->countPendingPosts();
        
        if (empty($posts)) {
            $text = "✅ <b>Barcha postlar ko'rib chiqilgan!</b>\n\n";
            $text .= "📊 Postlar statistikasi:\n";
            
            $postsByStatus = $this->db->getPostsByStatus();
            foreach ($postsByStatus as $status) {
                $statusName = [
                    'pending' => '⏳ Kutayotgan',
                    'approved' => '✅ Tasdiqlangan',
                    'rejected' => '❌ Rad etilgan'
                ][$status['status']] ?? $status['status'];
                $text .= "• {$statusName}: {$status['count']}\n";
            }
            
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '🔄 Yangilash', 'callback_data' => 'admin_refresh_posts']],
                    [['text' => '📊 Batafsil', 'callback_data' => 'admin_all_posts']]
                ]
            ];
            
            $this->telegram->sendMessage($userId, $text, $keyboard);
            return;
        }
        
        $post = $posts[0];
        $postNumber = $offset + 1;
        
        $text = "📋 <b>Post #{$postNumber} / {$totalPosts}</b>\n\n";
        $text .= "📂 <b>Kategoriya:</b> {$post['category_icon']} {$post['category_name']}\n";
        $text .= "👤 <b>Foydalanuvchi:</b> {$post['first_name']}";
        if ($post['username']) {
            $text .= " (@{$post['username']})";
        }
        $text .= "\n";
        $text .= "📞 <b>Telefon:</b> {$post['phone']}\n";
        $text .= "📅 <b>Sana:</b> " . date('d.m.Y H:i', strtotime($post['created_at'])) . "\n\n";
        $text .= "📝 <b>Matn:</b>\n{$post['content']}\n\n";
        $text .= "───────────────────────";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Tasdiqlash', 'callback_data' => "admin_approve_{$post['id']}"],
                    ['text' => '❌ Rad etish', 'callback_data' => "admin_reject_{$post['id']}"]
                ]
            ]
        ];
        
        // Navigatsiya tugmalari
        $navButtons = [];
        if ($offset > 0) {
            $navButtons[] = ['text' => '⬅️ Oldingi', 'callback_data' => "admin_prev_{$offset}"];
        }
        if ($offset + 1 < $totalPosts) {
            $navButtons[] = ['text' => '➡️ Keyingi', 'callback_data' => "admin_next_{$offset}"];
        }
        
        if (!empty($navButtons)) {
            $keyboard['inline_keyboard'][] = $navButtons;
        }
        
        // Qo'shimcha tugmalar
        $keyboard['inline_keyboard'][] = [
            ['text'
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->makeRequest('sendMessage', $data);
    }
    
    public function sendPhoto($chatId, $photo, $caption = null, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => 'HTML'
        ];
        
        if ($caption) {
            $data['caption'] = $caption;
        }
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->makeRequest('sendPhoto', $data);
    }
    
    public function sendMessageToChannel($text, $replyMarkup = null) {
        return $this->sendMessage(Config::CHANNEL_ID, $text, $replyMarkup);
    }
    
    public function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        if ($replyMarkup) {