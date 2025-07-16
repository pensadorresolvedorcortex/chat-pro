from datetime import datetime
from flask_sqlalchemy import SQLAlchemy


db = SQLAlchemy()


class Employee(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    cpf = db.Column(db.String(14), unique=True, nullable=False)
    phone = db.Column(db.String(20))
    address = db.Column(db.String(200))
    role = db.Column(db.String(80))
    salary = db.Column(db.Float)
    commission = db.Column(db.Float)


class Client(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    phone = db.Column(db.String(20))
    birth_date = db.Column(db.String(10))
    address = db.Column(db.String(200))
    preferences = db.Column(db.Text)


class Product(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(120), nullable=False)
    quantity = db.Column(db.Integer, default=0)
    min_quantity = db.Column(db.Integer, default=0)
    price = db.Column(db.Float, default=0.0)


class Appointment(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    client_id = db.Column(db.Integer, db.ForeignKey('client.id'))
    employee_id = db.Column(db.Integer, db.ForeignKey('employee.id'))
    date = db.Column(db.DateTime, default=datetime.utcnow)
    notes = db.Column(db.String(200))
    client = db.relationship('Client', backref=db.backref('appointments', lazy=True))
    employee = db.relationship('Employee', backref=db.backref('appointments', lazy=True))


class Expense(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200))
    amount = db.Column(db.Float)
    date = db.Column(db.DateTime, default=datetime.utcnow)


class Revenue(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200))
    amount = db.Column(db.Float)
    date = db.Column(db.DateTime, default=datetime.utcnow)
