CREATE TABLE IF NOT EXISTS headlines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  weight INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS offers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  offer_link VARCHAR(500) NOT NULL,
  cash_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  installments_qty INT UNSIGNED NOT NULL DEFAULT 1,
  installment_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  weight INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visitors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_uuid CHAR(36) NOT NULL UNIQUE,
  first_seen_at DATETIME NOT NULL,
  last_seen_at DATETIME NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  device_type VARCHAR(20) NULL,
  utm_source VARCHAR(150) NULL,
  utm_medium VARCHAR(150) NULL,
  utm_campaign VARCHAR(150) NULL,
  utm_content VARCHAR(150) NULL,
  utm_term VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_uuid CHAR(36) NOT NULL,
  headline_id INT UNSIGNED NULL,
  offer_id INT UNSIGNED NULL,
  url VARCHAR(800) NULL,
  referrer VARCHAR(800) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  device_type VARCHAR(20) NULL,
  utm_source VARCHAR(150) NULL,
  utm_medium VARCHAR(150) NULL,
  utm_campaign VARCHAR(150) NULL,
  utm_content VARCHAR(150) NULL,
  utm_term VARCHAR(150) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_visits_created (created_at),
  INDEX idx_visits_headline (headline_id),
  INDEX idx_visits_offer (offer_id),
  INDEX idx_visits_visitor (visitor_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_uuid CHAR(36) NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(50) NULL,
  headline_id INT UNSIGNED NULL,
  offer_id INT UNSIGNED NULL,
  offer_name VARCHAR(150) NULL,
  offer_link VARCHAR(500) NULL,
  cash_price DECIMAL(10,2) NULL,
  installments_qty INT UNSIGNED NULL,
  installment_price DECIMAL(10,2) NULL,
  utm_source VARCHAR(150) NULL,
  utm_medium VARCHAR(150) NULL,
  utm_campaign VARCHAR(150) NULL,
  utm_content VARCHAR(150) NULL,
  utm_term VARCHAR(150) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_leads_created (created_at),
  INDEX idx_leads_visitor (visitor_uuid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS clicks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_uuid CHAR(36) NOT NULL,
  lead_id INT UNSIGNED NULL,
  headline_id INT UNSIGNED NULL,
  offer_id INT UNSIGNED NULL,
  offer_link VARCHAR(500) NULL,
  button_id VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_clicks_created (created_at),
  INDEX idx_clicks_visitor (visitor_uuid),
  INDEX idx_clicks_button (button_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhook_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  payload MEDIUMTEXT NULL,
  response_body MEDIUMTEXT NULL,
  http_status INT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_webhook_lead (lead_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value MEDIUMTEXT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO headlines (title, description, is_active, weight)
SELECT 'Descubra o método que transforma sua oferta em vendas todos os dias', 'Assista ao vídeo completo e veja como aplicar ainda hoje.', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM headlines LIMIT 1);

INSERT INTO offers (name, offer_link, cash_price, installments_qty, installment_price, description, is_active, weight)
SELECT 'Oferta Principal', 'https://seulinkdecheckout.com', 297.00, 12, 29.70, 'Acesso completo com condição especial.', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM offers LIMIT 1);

INSERT INTO settings (setting_key, setting_value, updated_at)
VALUES ('vturb_embed', '', NOW())
ON DUPLICATE KEY UPDATE setting_key = setting_key;

