CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       name VARCHAR(50) NOT NULL,
                       surname VARCHAR(50) NOT NULL,
                       email VARCHAR(100) NOT NULL UNIQUE,
                       password VARCHAR(255) NOT NULL,
                       phone VARCHAR(20) NOT NULL,
                       role ENUM('client', 'employee', 'admin') DEFAULT 'client',
                       active TINYINT(1) DEFAULT 1,
                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE service_categories (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    name VARCHAR(100) NOT NULL,
                                    description TEXT
);


CREATE TABLE services (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          category_id INT,
                          name VARCHAR(150) NOT NULL,
                          description TEXT,
                          duration INT NOT NULL,
                          price DECIMAL(10, 2) NOT NULL,
                          active TINYINT(1) DEFAULT 1,
                          FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE SET NULL
);


CREATE TABLE employee_services (
                                   employee_id INT,
                                   service_id INT,
                                   PRIMARY KEY (employee_id, service_id),
                                   FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
                                   FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);


CREATE TABLE employee_availability (
                                       id INT AUTO_INCREMENT PRIMARY KEY,
                                       employee_id INT,
                                       day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
                                       start_time TIME NOT NULL,
                                       end_time TIME NOT NULL,
                                       FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE reservations (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              user_id INT,
                              employee_id INT,
                              service_id INT,
                              reservation_date DATE NOT NULL,
                              start_time TIME NOT NULL,
                              end_time TIME NOT NULL,
                              status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
                              comment TEXT,
                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                              FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE RESTRICT,
                              FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT
);


INSERT INTO service_categories (name, description) VALUES
                                                       ('Kosmetyka Wnętrza', 'Kompleksowe czyszczenie i detailing wnętrza pojazdów'),
                                                       ('Ochrona Lakieru', 'Korekty lakieru oraz aplikacja powłok ochronnych');


INSERT INTO services (category_id, name, description, duration, price) VALUES
                                                                           (1, 'Pranie i Detailing Wnętrza', 'Dokładne odkurzanie, pranie ekstrakcyjne i czyszczenie plastików', 180, 450.00), -- 3 godziny
                                                                           (2, 'Korekta Lakieru (1-etapowa)', 'Usunięcie do 60% zarysowań, odświeżenie połysku lakieru', 360, 900.00),     -- 6 godzin
                                                                           (2, 'Powłoka Ceramiczna 9H', 'Aplikacja powłoki ceramicznej na lakier (ochrona na 3 lata)', 480, 1500.00);    -- 8 godzin
