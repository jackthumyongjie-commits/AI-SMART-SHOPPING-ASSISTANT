-- Seed Data for AI Smart Shopping Assistant

-- Default Administrator (password: admin123)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Store Administrator', 'admin@example.com', '$2y$10$ee3dISEILRWeCsl1Fw9i8uWqZtdkCLl4WsYpkCAT6hp5UG9Z5MqgG', 'admin');

-- Default AI Settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('ai_provider', 'agnes'),
('agnes_api_url', 'https://apihub.agnes-ai.com/v1'),
('agnes_api_key', ''),
('agnes_model', 'agnes-2.5-flash'),
('agnes_fallback_models', 'agnes-2.0-flash'),
('gemini_api_url', 'https://generativelanguage.googleapis.com/v1beta'),
('gemini_api_key', ''),
('gemini_model', 'gemini-3.5-flash-lite'),
('gemini_fallback_models', 'gemini-2.5-flash-lite, gemini-flash-lite-latest');

-- Sample Retail Products
INSERT IGNORE INTO products (sku, barcode, name, detect_keywords, price, description, image_path) VALUES
('SKU-INH-001', '8857200535366', 'Hong Thai Traditional Herbal Inhaler', 'hong thai, herbal inhaler, green bottle, thai inhaler, menthol inhaler', 5.50, 'Famous Thai traditional herbal inhaler with natural herbs, eucalyptus, and cooling menthol aroma. Essential for refresh and dizziness relief.', 'uploads/products/sample_inhaler.png'),
('SKU-COF-002', '8850123456789', 'Nescafé Blend & Brew Original Coffee Mix', 'nescafe, blend brew, coffee mix, red packet coffee, instant coffee', 14.90, 'Rich and smooth 3-in-1 instant coffee mix made with premium Robusta and Arabica coffee beans.', 'uploads/products/sample_coffee.png'),
('SKU-DRK-003', '8851234567890', 'Red Bull Energy Drink 250ml', 'red bull, energy drink, gold can, bull logo, energy drink 250ml', 4.20, 'Vitalizing energy drink that refreshes body and mind with caffeine, taurine, and B-group vitamins.', 'uploads/products/sample_redbull.png'),
('SKU-CHO-004', '7622210987654', 'Cadbury Dairy Milk Chocolate Bar 160g', 'cadbury, dairy milk, chocolate bar, purple wrapper, milk chocolate', 8.90, 'Classic smooth and creamy Cadbury milk chocolate made with a glass and a half of fresh milk.', 'uploads/products/sample_chocolate.png'),
('SKU-TEA-005', '8859876543210', 'ChaTraMue Thai Green Tea Mix 200g', 'chatramue, thai green tea, green tea mix, tin can tea, thai tea', 12.50, 'Original Thai green milk tea powder mix by ChaTraMue, ideal for making authentic milk tea, boba tea, and iced green tea.', 'uploads/products/sample_greentea.png'),
('SKU-WAT-006', '9556001122334', 'Evian Natural Mineral Water 500ml', 'evian, mineral water, water bottle, pink cap water, natural spring water', 3.80, 'Pure natural mineral water sourced directly from the French Alps, rich in natural minerals.', 'uploads/products/sample_water.png');
