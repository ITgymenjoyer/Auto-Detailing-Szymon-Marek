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


INSERT INTO users (id, name, surname, email, password, phone, role, active) VALUES 
(1, 'Jan', 'Kowalski', 'admin@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '123456789', 'admin', 1),
(2, 'Piotr', 'Nowak', 'pracownik@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '987654321', 'employee', 1),
(3, 'Marek', 'Zieliński', 'marek.pracownik@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '555111222', 'employee', 1),
(4, 'Tomasz', 'Wójcik', 'tomasz.pracownik@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '555333444', 'employee', 1),
(5, 'Anna', 'Wiśniewska', 'klient@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '555666777', 'client', 1),
(6, 'Katarzyna', 'Kowalczyk', 'katarzyna.klient@detailing.pl', '$2y$10$1lYjv3pw.AYem7vJSzsbnOBP8fbSnm9gInDcy5cu.03UkhcND0XQW', '555888999', 'client', 1);

INSERT INTO service_categories (id, name, description) VALUES
(1, 'Kosmetyka Wnętrza', 'Kompleksowe czyszczenie i detailing wnętrza pojazdów'),
(2, 'Ochrona Lakieru', 'Korekty lakieru oraz aplikacja powłok ochronnych'),
(3, 'Pielęgnacja Szyb i Felg', 'Zabezpieczenie szyb powłokami hydrofobowymi oraz pełny detailing felg'),
(4, 'Renowacja Reflektorów', 'Przywracanie przejrzystości i ochrona kloszy lamp');

INSERT INTO services (id, category_id, name, description, duration, price, active) VALUES
(1, 1, 'Pranie i Detailing Wnętrza', 'Dokładne odkurzanie, pranie ekstrakcyjne i czyszczenie plastików', 180, 450.00, 1),
(2, 1, 'Impregnacja Skórzanej Tapicerki', 'Czyszczenie i zabezpieczenie skór specjalistycznymi preparatami', 120, 300.00, 1),
(3, 2, 'Korekta Lakieru (1-etapowa)', 'Usunięcie do 60% zarysowań, odświeżenie połysku lakieru', 360, 900.00, 1),
(4, 2, 'Powłoka Ceramiczna 9H', 'Aplikacja powłoki ceramicznej na lakier (ochrona na 3 lata)', 480, 1500.00, 1),
(5, 3, 'Niewidzialna Wycieraczka', 'Aplikacja powłoki hydrofobowej na szyby przednią i boczne', 60, 150.00, 1),
(6, 3, 'Detailing i Czyszczenie Felg', 'Demontaż felg, czyszczenie od wewnątrz i zewnątrz, zabezpieczenie woskiem', 150, 400.00, 1),
(7, 4, 'Polerowanie i Zabezpieczenie Reflektorów', 'Szlifowanie, polerowanie oraz aplikacja powłoki UV na klosze lamp', 90, 250.00, 1);


INSERT INTO employee_services (employee_id, service_id) VALUES 
(2, 1), (2, 2), (2, 3),
(3, 1), (3, 4), (3, 5),
(4, 2), (4, 6), (4, 7);

INSERT INTO employee_availability (employee_id, day_of_week, start_time, end_time) VALUES 
(2, 'Monday', '08:00:00', '16:00:00'),
(2, 'Tuesday', '08:00:00', '16:00:00'),
(2, 'Wednesday', '08:00:00', '16:00:00'),
(2, 'Thursday', '08:00:00', '16:00:00'),
(2, 'Friday', '08:00:00', '16:00:00'),
(3, 'Monday', '10:00:00', '18:00:00'),
(3, 'Wednesday', '10:00:00', '18:00:00'),
(3, 'Friday', '10:00:00', '18:00:00'),
(4, 'Tuesday', '09:00:00', '17:00:00'),
(4, 'Thursday', '09:00:00', '17:00:00'),
(4, 'Saturday', '09:00:00', '14:00:00');

INSERT INTO reservations (user_id, employee_id, service_id, reservation_date, start_time, end_time, status, comment) VALUES 
(5, 2, 1, '2026-11-15', '10:00:00', '13:00:00', 'pending', 'Proszę o dokładne wyczyszczenie wnętrza.'),
(6, 3, 4, '2026-11-16', '10:00:00', '18:00:00', 'confirmed', 'Auto zostawione rano.'),
(5, 4, 7, '2026-11-10', '09:00:00', '10:30:00', 'completed', 'Reflektory zrobione idealnie.');
