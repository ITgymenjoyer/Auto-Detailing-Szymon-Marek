# DOKUMENTACJA BAZY DANYCH

1. OPIS BAZY

---

Baza danych służy do obsługi systemu rezerwacji usług detailingowych.
Przechowuje użytkowników, kategorie i usługi, pracowników wraz z ich
dostępnością oraz rezerwacje klientów.

Główne tabele:

* users
* service_categories
* services
* employee_services
* employee_availability
* reservations

2. STRUKTURA TABEL

---

## 2.1. users

Przechowuje dane wszystkich użytkowników systemu.

Pola:

* id          - unikalny identyfikator użytkownika (PRIMARY KEY),
* name        - imię,
* surname     - nazwisko,
* email       - unikalny adres e-mail,
* password    - hasło użytkownika,
* phone       - numer telefonu,
* role        - rola: client, employee lub admin,
* active      - 1 = konto aktywne, 0 = konto nieaktywne,
* created_at  - data i czas utworzenia konta.

Pracownicy również są przechowywani w tej tabeli. Ich rozróżnienie
od klientów i administratorów odbywa się za pomocą pola "role".

## 2.2. service_categories

Przechowuje kategorie usług.

Pola:

* id          - identyfikator kategorii,
* name        - nazwa kategorii,
* description - opis kategorii.

## 2.3. services

Przechowuje oferowane usługi.

Pola:

* id          - identyfikator usługi,
* category_id - identyfikator kategorii,
* name        - nazwa usługi,
* description - opis usługi,
* duration    - czas trwania w minutach,
* price       - cena usługi,
* active      - 1 = usługa aktywna, 0 = nieaktywna.

Relacja:
services.category_id -> service_categories.id

Zastosowano ON DELETE SET NULL. Oznacza to, że usunięcie kategorii
nie usuwa usług. Pole category_id zostanie wtedy ustawione na NULL.

## 2.4. employee_services

Tabela pośrednia łącząca pracowników z usługami.

Pola:

* employee_id - identyfikator pracownika,
* service_id  - identyfikator usługi.

Klucz główny:
(employee_id, service_id)

Jest to relacja wiele-do-wielu:

* jeden pracownik może wykonywać wiele usług,
* jedna usługa może być wykonywana przez wielu pracowników.

ON DELETE CASCADE usuwa powiązanie po usunięciu pracownika lub usługi.

## 2.5. employee_availability

Przechowuje godziny dostępności pracowników.

Pola:

* id          - identyfikator wpisu,
* employee_id - identyfikator pracownika,
* day_of_week - dzień tygodnia,
* start_time  - godzina rozpoczęcia dostępności,
* end_time    - godzina zakończenia dostępności.

Relacja:
employee_availability.employee_id -> users.id

ON DELETE CASCADE powoduje usunięcie dostępności po usunięciu
powiązanego użytkownika.

## 2.6. reservations

Przechowuje rezerwacje klientów.

Pola:

* id               - identyfikator rezerwacji,
* user_id          - identyfikator klienta,
* employee_id      - identyfikator pracownika,
* service_id       - identyfikator usługi,
* reservation_date - data rezerwacji,
* start_time       - godzina rozpoczęcia,
* end_time         - godzina zakończenia,
* status           - pending, confirmed, completed lub cancelled,
* comment          - komentarz do rezerwacji,
* created_at       - data i czas utworzenia rezerwacji.

Relacje:

* user_id -> users.id
* employee_id -> users.id
* service_id -> services.id

Dla klienta zastosowano ON DELETE CASCADE.
Dla pracownika i usługi zastosowano ON DELETE RESTRICT.

3. RELACJE MIĘDZY TABELAMI

---

service_categories
|
| 1:N
v
services
|
| N:N
v
employee_services
|
v
users (employee)

users (employee)
|
| 1:N
v
employee_availability

users (client)
|
| 1:N
v
reservations

Najważniejsze relacje:

* jedna kategoria może posiadać wiele usług,
* jedna usługa należy do jednej kategorii lub może nie mieć kategorii,
* pracownik może wykonywać wiele usług,
* usługa może być wykonywana przez wielu pracowników,
* pracownik może mieć wiele wpisów dostępności,
* klient może posiadać wiele rezerwacji,
* pracownik może być przypisany do wielu rezerwacji,
* usługa może występować w wielu rezerwacjach.

4. DZIAŁANIE SYSTEMU REZERWACJI

---

Typowy proces wygląda następująco:

1. Klient posiada konto w tabeli users z rolą "client".

2. Wybiera kategorię i usługę.

3. System sprawdza pracowników przypisanych do usługi
   w tabeli employee_services.

4. System sprawdza dostępność pracownika w employee_availability.

5. Klient wybiera datę i godzinę.

6. Rezerwacja jest zapisywana w tabeli reservations.

7. Początkowy status rezerwacji to "pending".

8. Rezerwacja może następnie zostać potwierdzona, zakończona
   lub anulowana.

9. DANE POCZĄTKOWE

---

W bazie dodano dwie przykładowe kategorie:

1. Kosmetyka Wnętrza
   Kompleksowe czyszczenie i detailing wnętrza pojazdów.

2. Ochrona Lakieru
   Korekty lakieru oraz aplikacja powłok ochronnych.

Dodano również trzy przykładowe usługi:

1. Pranie i Detailing Wnętrza
   Czas: 180 minut
   Cena: 450.00

2. Korekta Lakieru (1-etapowa)
   Czas: 360 minut
   Cena: 900.00

3. Powłoka Ceramiczna 9H
   Czas: 480 minut
   Cena: 1500.00

4. INTEGRALNOŚĆ DANYCH

---

PRIMARY KEY
Każda tabela posiada klucz główny identyfikujący rekord.
W employee_services kluczem jest para:
(employee_id, service_id).

FOREIGN KEY
Klucze obce zapewniają poprawne powiązania pomiędzy tabelami.

ON DELETE CASCADE
Usuwa rekordy zależne razem z rekordem nadrzędnym.

ON DELETE SET NULL
Po usunięciu kategorii pozostawia usługę w bazie, ale ustawia
jej category_id na NULL.

ON DELETE RESTRICT
Blokuje usunięcie rekordu, jeżeli istnieją powiązane rezerwacje.

UNIQUE
Pole email w tabeli users jest unikalne.

7. ROLE UŻYTKOWNIKÓW

---

## client

Klient systemu, który może korzystać z usług i tworzyć rezerwacje.

## employee

Pracownik wykonujący usługi. Jego konto jest używane również
do przechowywania usług, które wykonuje, oraz godzin dostępności.

## admin

Administrator systemu. Rola może być wykorzystywana przez aplikację
do obsługi funkcji administracyjnych.

8. UWAGI TECHNICZNE

---

* duration jest podawane w minutach.
* price używa typu DECIMAL(10,2), czyli dwóch miejsc po przecinku.
* active używa TINYINT(1): 1 oznacza aktywność, 0 brak aktywności.
* role, status i day_of_week wykorzystują ENUM.
* Hasła powinny być przechowywane jako bezpieczne hashe,
  a nie jako tekst jawny.
* Rezerwacja przechowuje datę oraz czas rozpoczęcia i zakończenia,
  co pozwala zarządzać harmonogramem.

9. PODSUMOWANIE

---

Baza reprezentuje system rezerwacji usług detailingowych.

Tabela users odpowiada za użytkowników i pracowników.
service_categories i services przechowują ofertę usług.
employee_services określa, jakie usługi wykonują pracownicy.
employee_availability przechowuje ich godziny dostępności.
reservations przechowuje rezerwacje klientów.

Klucze główne, klucze obce oraz reguły ON DELETE pomagają zachować
spójność danych i prawidłowe powiązania pomiędzy tabelami.
