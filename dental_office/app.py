import sqlite3
from datetime import datetime

DB_FILE = 'dental_office.db'

SCHEMA = """
CREATE TABLE IF NOT EXISTS patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS dentists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    specialty TEXT
);

CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL,
    dentist_id INTEGER NOT NULL,
    date TEXT NOT NULL,
    service TEXT,
    FOREIGN KEY(patient_id) REFERENCES patients(id),
    FOREIGN KEY(dentist_id) REFERENCES dentists(id)
);
"""

def get_conn():
    conn = sqlite3.connect(DB_FILE)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    with get_conn() as conn:
        conn.executescript(SCHEMA)

# Patient operations

def add_patient(name, phone='', email='', notes=''):
    with get_conn() as conn:
        conn.execute(
            'INSERT INTO patients (name, phone, email, notes) VALUES (?,?,?,?)',
            (name, phone, email, notes),
        )


def list_patients():
    with get_conn() as conn:
        cur = conn.execute('SELECT * FROM patients')
        return cur.fetchall()


# Dentist operations

def add_dentist(name, specialty=''):
    with get_conn() as conn:
        conn.execute(
            'INSERT INTO dentists (name, specialty) VALUES (?, ?)',
            (name, specialty),
        )


def list_dentists():
    with get_conn() as conn:
        cur = conn.execute('SELECT * FROM dentists')
        return cur.fetchall()


# Appointment operations

def add_appointment(patient_id, dentist_id, date, service=''):
    with get_conn() as conn:
        conn.execute(
            'INSERT INTO appointments (patient_id, dentist_id, date, service) VALUES (?,?,?,?)',
            (patient_id, dentist_id, date, service),
        )


def list_appointments(start_date=None, end_date=None):
    query = 'SELECT a.id, p.name as patient, d.name as dentist, a.date, a.service FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN dentists d ON a.dentist_id=d.id'
    params = []
    if start_date:
        query += ' WHERE date>=?'
        params.append(start_date)
    if end_date:
        query += ' AND date<=?' if params else ' WHERE date<=?'
        params.append(end_date)
    query += ' ORDER BY date'
    with get_conn() as conn:
        cur = conn.execute(query, params)
        return cur.fetchall()


def main_menu():
    while True:
        print('\n-- Dental Office Management --')
        print('1. Add patient')
        print('2. List patients')
        print('3. Add dentist')
        print('4. List dentists')
        print('5. Schedule appointment')
        print('6. List appointments')
        print('0. Exit')
        choice = input('Select option: ')
        if choice == '1':
            name = input('Name: ')
            phone = input('Phone: ')
            email = input('Email: ')
            notes = input('Notes: ')
            add_patient(name, phone, email, notes)
            print('Patient added.')
        elif choice == '2':
            for p in list_patients():
                print(f"{p['id']}: {p['name']} - {p['phone']} - {p['email']}")
        elif choice == '3':
            name = input('Name: ')
            specialty = input('Specialty: ')
            add_dentist(name, specialty)
            print('Dentist added.')
        elif choice == '4':
            for d in list_dentists():
                print(f"{d['id']}: {d['name']} - {d['specialty']}")
        elif choice == '5':
            patient_id = input('Patient ID: ')
            dentist_id = input('Dentist ID: ')
            date = input('Date (YYYY-MM-DD HH:MM): ')
            service = input('Service: ')
            # Validate datetime
            try:
                datetime.strptime(date, '%Y-%m-%d %H:%M')
            except ValueError:
                print('Invalid date format.')
                continue
            add_appointment(int(patient_id), int(dentist_id), date, service)
            print('Appointment scheduled.')
        elif choice == '6':
            start = input('Start date (YYYY-MM-DD, optional): ')
            end = input('End date (YYYY-MM-DD, optional): ')
            start = start or None
            end = end or None
            for a in list_appointments(start, end):
                print(f"{a['id']}: {a['date']} - {a['patient']} with {a['dentist']} ({a['service']})")
        elif choice == '0':
            break
        else:
            print('Invalid option')


if __name__ == '__main__':
    init_db()
    main_menu()
