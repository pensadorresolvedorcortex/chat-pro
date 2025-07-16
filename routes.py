from datetime import datetime
from flask import Blueprint, render_template, request, redirect, url_for
from models import db, Employee, Client, Product, Appointment, Expense, Revenue

bp = Blueprint('bp', __name__)


@bp.route('/')
def index():
    return render_template('index.html')


# Employees
@bp.route('/employees')
def employees():
    all_employees = Employee.query.all()
    return render_template('employees.html', employees=all_employees)


@bp.route('/employees/new', methods=['GET', 'POST'])
def new_employee():
    if request.method == 'POST':
        employee = Employee(
            name=request.form['name'],
            cpf=request.form['cpf'],
            phone=request.form.get('phone'),
            address=request.form.get('address'),
            role=request.form.get('role'),
            salary=request.form.get('salary') or 0,
            commission=request.form.get('commission') or 0,
        )
        db.session.add(employee)
        db.session.commit()
        return redirect(url_for('bp.employees'))
    return render_template('new_employee.html')


# Clients
@bp.route('/clients')
def clients():
    all_clients = Client.query.all()
    return render_template('clients.html', clients=all_clients)


@bp.route('/clients/new', methods=['GET', 'POST'])
def new_client():
    if request.method == 'POST':
        client = Client(
            name=request.form['name'],
            phone=request.form.get('phone'),
            birth_date=request.form.get('birth_date'),
            address=request.form.get('address'),
            preferences=request.form.get('preferences'),
        )
        db.session.add(client)
        db.session.commit()
        return redirect(url_for('bp.clients'))
    return render_template('new_client.html')


# Products
@bp.route('/products')
def products():
    all_products = Product.query.all()
    return render_template('products.html', products=all_products)


@bp.route('/products/new', methods=['GET', 'POST'])
def new_product():
    if request.method == 'POST':
        product = Product(
            name=request.form['name'],
            quantity=request.form.get('quantity') or 0,
            min_quantity=request.form.get('min_quantity') or 0,
            price=request.form.get('price') or 0,
        )
        db.session.add(product)
        db.session.commit()
        return redirect(url_for('bp.products'))
    return render_template('new_product.html')


# Appointments
@bp.route('/appointments')
def appointments():
    all_appointments = Appointment.query.order_by(Appointment.date.desc()).all()
    return render_template('appointments.html', appointments=all_appointments)


@bp.route('/appointments/new', methods=['GET', 'POST'])
def new_appointment():
    clients = Client.query.all()
    employees = Employee.query.all()
    if request.method == 'POST':
        appointment = Appointment(
            client_id=request.form['client_id'],
            employee_id=request.form['employee_id'],
            date=datetime.fromisoformat(request.form['date']),
            notes=request.form.get('notes'),
        )
        db.session.add(appointment)
        db.session.commit()
        return redirect(url_for('bp.appointments'))
    return render_template('new_appointment.html', clients=clients, employees=employees)


# Finances
@bp.route('/finances')
def finances():
    revenues = Revenue.query.order_by(Revenue.date.desc()).all()
    expenses = Expense.query.order_by(Expense.date.desc()).all()
    total_revenue = sum(r.amount for r in revenues)
    total_expense = sum(e.amount for e in expenses)
    return render_template(
        'finances.html',
        revenues=revenues,
        expenses=expenses,
        total_revenue=total_revenue,
        total_expense=total_expense,
    )


@bp.route('/finances/expense', methods=['GET', 'POST'])
def new_expense():
    if request.method == 'POST':
        expense = Expense(
            name=request.form['name'],
            amount=float(request.form['amount']),
            date=datetime.fromisoformat(request.form['date']),
        )
        db.session.add(expense)
        db.session.commit()
        return redirect(url_for('bp.finances'))
    return render_template('new_expense.html')


@bp.route('/finances/revenue', methods=['GET', 'POST'])
def new_revenue():
    if request.method == 'POST':
        revenue = Revenue(
            name=request.form['name'],
            amount=float(request.form['amount']),
            date=datetime.fromisoformat(request.form['date']),
        )
        db.session.add(revenue)
        db.session.commit()
        return redirect(url_for('bp.finances'))
    return render_template('new_revenue.html')
