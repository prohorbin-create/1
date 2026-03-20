import streamlit as st
import pandas as pd
import plotly.express as px
from datetime import datetime

# Настройка страницы
st.set_page_config(page_title='Автоспектр', layout='wide', page_icon='🔧')

# Кастомный CSS в стиле Fit Service
def local_css():
    st.markdown("""
    <style>
    :root {
        --fit-orange: #FBAD18;
        --fit-black: #1A1A1A;
        --fit-gray: #F0F2F6;
        --fit-white: #FFFFFF;
    }

    /* Фон приложения */
    .stApp {
        background-color: var(--fit-gray);
    }

    /* Заголовки */
    h1, h2, h3 {
        color: var(--fit-black) !important;
        font-family: 'Inter', sans-serif;
        font-weight: 800 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Брендированный логотип */
    .brand-logo {
        font-size: 2.2rem;
        font-weight: 900;
        color: var(--fit-black);
        text-align: center;
        margin-bottom: 5px;
        letter-spacing: -1px;
    }
    .brand-logo span { color: var(--fit-orange); }

    /* Кнопки */
    div.stButton > button {
        background-color: var(--fit-orange) !important;
        color: var(--fit-black) !important;
        border: none !important;
        font-weight: bold !important;
        border-radius: 6px !important;
        padding: 12px 20px !important;
        width: 100%;
        transition: 0.3s transform;
    }
    div.stButton > button:hover {
        transform: translateY(-2px);
        background-color: #e59a16 !important;
    }

    /* Карточки авто */
    .car-card {
        background-color: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-left: 6px solid var(--fit-orange);
        margin-bottom: 1rem;
    }

    /* Боковое меню */
    [data-testid="stSidebar"] {
        background-color: var(--fit-black);
    }
    [data-testid="stSidebar"] * {
        color: #E0E0E0 !important;
    }
    [data-testid="stSidebarNav"] li div a span {
        color: white !important;
    }

    /* Формы */
    .stTextInput input, .stSelectbox div[data-baseweb="select"], textarea {
        border-radius: 6px !important;
        border: 1px solid #ddd !important;
    }

    /* Информационные блоки */
    .contact-info {
        text-align: center;
        background: white;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 2rem;
        border: 1px solid #eee;
    }
    
    .guide-box {
        background: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #ffeeba;
        margin-bottom: 20px;
    }
    </style>
    """, unsafe_allow_html=True)

local_css()

# Инициализация базы данных (в session_state)
ADMIN_PHONE = '7000'

if 'db_users' not in st.session_state:
    st.session_state.db_users = {
        ADMIN_PHONE: {'name': 'Администратор', 'pass': 'admin'},
        '79132034981': {'name': 'Иван Иванов', 'pass': '123'}
    }

if 'db_cars' not in st.session_state:
    st.session_state.db_cars = [
        {'id': 1, 'phone': '79132034981', 'brand': 'Toyota', 'model': 'Camry', 'plate': 'А123БВ154', 'year': 2019}
    ]

if 'db_orders' not in st.session_state:
    st.session_state.db_orders = [
        {'id': 1001, 'car_id': 1, 'date': '2024-03-15', 'service': 'Замена масла и фильтров', 'cost': 6500, 'status': 'Выполнено'}
    ]

if 'db_appointments' not in st.session_state:
    st.session_state.db_appointments = []

if 'user_auth' not in st.session_state:
    st.session_state.user_auth = None

# --- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ---
def get_client_cars(phone):
    return [c for c in st.session_state.db_cars if c['phone'] == phone]

def get_car_history(car_ids):
    return [o for o in st.session_state.db_orders if o['car_id'] in car_ids]

# --- ЭКРАН АВТОРИЗАЦИИ / РЕГИСТРАЦИИ ---
def show_auth():
    col1, col2, col3 = st.columns([1, 2, 1])
    with col2:
        st.markdown("<div class='brand-logo'>АВТО<span>СПЕКТР</span></div>", unsafe_allow_html=True)
        st.markdown("""
        <div class='contact-info'>
            <b>📞 +7 913 203 4981</b><br>
            📍 г. Новосибирск, ул. Плотинная, 1Б<br>
            <i>(вход через Родные масла)</i>
        </div>
        """, unsafe_allow_html=True)

        tab1, tab2 = st.tabs(["Вход", "Регистрация"])
        
        with tab1:
            login_phone = st.text_input("Номер телефона", placeholder="79XXXXXXXXX")
            login_pass = st.text_input("Пароль", type="password")
            if st.button("ВОЙТИ"):
                clean_p = "".join(filter(str.isdigit, login_phone))
                user = st.session_state.db_users.get(clean_p)
                if user and user['pass'] == login_pass:
                    st.session_state.user_auth = clean_p
                    st.rerun()
                else:
                    st.error("Ошибка входа: неверный номер или пароль")

        with tab2:
            st.write("Создайте личный кабинет")
            reg_name = st.text_input("Ваше Имя")
            reg_phone = st.text_input("Телефон")
            reg_pass = st.text_input("Придумайте пароль", type="password")
            if st.button("ЗАРЕГИСТРИРОВАТЬСЯ"):
                clean_p = "".join(filter(str.isdigit, reg_phone))
                if clean_p and reg_name and reg_pass:
                    if clean_p in st.session_state.db_users:
                        st.warning("Этот номер уже зарегистрирован")
                    else:
                        st.session_state.db_users[clean_p] = {'name': reg_name, 'pass': reg_pass}
                        st.success("Регистрация успешна! Теперь войдите во вкладке 'Вход'")
                else:
                    st.error("Заполните все поля")

# --- ПАНЕЛЬ АДМИНИСТРАТОРА ---
def show_admin():
    st.sidebar.markdown("### ПАНЕЛЬ УПРАВЛЕНИЯ")
    page = st.sidebar.radio("Раздел", ["📋 Заявки на ремонт", "🛠 Заказ-наряды", "👥 База клиентов", "📊 Аналитика", "Выход"])

    if page == "Выход":
        st.session_state.user_auth = None
        st.rerun()

    elif page == "📋 Заявки на ремонт":
        st.title("ОТСЛЕЖИВАНИЕ ЗАПИСЕЙ")
        if not st.session_state.db_appointments:
            st.info("Новых заявок пока нет")
        else:
            df_app = pd.DataFrame(st.session_state.db_appointments)
            st.markdown("**Редактируйте статусы и данные прямо в таблице:**")
            edited_df = st.data_editor(df_app, num_rows="dynamic", use_container_width=True)
            if st.button("Сохранить изменения в записях"):
                st.session_state.db_appointments = edited_df.to_dict('records')
                st.success("Записи обновлены")

    elif page == "🛠 Заказ-наряды":
        st.title("УПРАВЛЕНИЕ ЗАКАЗ-НАРЯДАМИ")
        
        with st.expander("➕ Создать новый заказ-наряд", expanded=False):
            with st.form("new_order_form"):
                all_cars = {f"{c['plate']} ({c['brand']} {c['model']})": c['id'] for c in st.session_state.db_cars}
                sel_car = st.selectbox("Выберите авто", options=list(all_cars.keys()))
                ord_id = st.number_input("№ Заказ-наряда", value=len(st.session_state.db_orders)+1000)
                ord_date = st.date_input("Дата")
                ord_svc = st.text_area("Работы и запчасти")
                ord_cost = st.number_input("Сумма (₽)", min_value=0, step=100)
                ord_stat = st.selectbox("Статус", ["В работе", "Выполнено", "Отменено"])
                
                if st.form_submit_button("ОФОРМИТЬ"):
                    st.session_state.db_orders.append({
                        'id': ord_id, 'car_id': all_cars[sel_car], 'date': str(ord_date), 
                        'service': ord_svc, 'cost': ord_cost, 'status': ord_stat
                    })
                    st.success("Заказ-наряд создан!")

        st.markdown("---")
        df_orders = pd.DataFrame(st.session_state.db_orders)
        st.markdown("**Список всех заказ-нарядов (редактируемый):**")
        edited_orders = st.data_editor(df_orders, num_rows="dynamic", use_container_width=True)
        if st.button("Обновить базу заказ-нарядов"):
            st.session_state.db_orders = edited_orders.to_dict('records')
            st.rerun()

    elif page == "👥 База клиентов":
        st.title("КЛИЕНТЫ И АВТО")
        clients_data = []
        for p, info in st.session_state.db_users.items():
            if p == ADMIN_PHONE: continue
            u_cars = [f"{c['brand']} {c['model']} ({c['plate']})" for c in st.session_state.db_cars if c['phone'] == p]
            clients_data.append({'Имя': info['name'], 'Телефон': p, 'Автомобили': ", ".join(u_cars)})
        st.table(pd.DataFrame(clients_data))

# --- КАБИНЕТ КЛИЕНТА ---
def show_client():
    phone = st.session_state.user_auth
    user = st.session_state.db_users[phone]

    st.sidebar.markdown(f"<div style='text-align:center; padding:10px;'><h3>{user['name']}</h3></div>", unsafe_allow_html=True)
    page = st.sidebar.radio("Навигация", ["🚗 Гараж", "📅 Запись на сервис", "📋 История обслуживания", "❓ Справка", "Выход"])
    
    st.sidebar.markdown("---")
    st.sidebar.info("📞 +7 913 203 4981\n📍 ул. Плотинная, 1Б")

    if page == "Выход":
        st.session_state.user_auth = None
        st.rerun()

    elif page == "🚗 Гараж":
        st.title("МОЙ ГАРАЖ")
        my_cars = get_client_cars(phone)
        
        if not my_cars:
            st.info("В гараже пока пусто. Добавьте свой автомобиль!")
        else:
            for c in my_cars:
                st.markdown(f"""
                <div class='car-card'>
                    <h2 style='margin:0;'>{c['brand']} {c['model']}</h2>
                    <p style='color: #FBAD18; font-size: 1.5rem; font-weight: bold; margin: 10px 0;'>{c['plate']}</p>
                    <p style='color: gray; margin: 0;'>Год выпуска: {c['year']}</p>
                </div>
                """, unsafe_allow_html=True)

        with st.expander("➕ Добавить автомобиль"):
            with st.form("add_car"):
                c_brand = st.text_input("Марка")
                c_model = st.text_input("Модель")
                c_plate = st.text_input("Гос. номер (например, А000АА154)")
                c_year = st.number_input("Год", 1990, 2025, 2015)
                if st.form_submit_button("Сохранить"):
                    if c_brand and c_model and c_plate:
                        st.session_state.db_cars.append({
                            'id': len(st.session_state.db_cars)+1, 'phone': phone, 
                            'brand': c_brand, 'model': c_model, 'plate': c_plate, 'year': c_year
                        })
                        st.success("Авто добавлено!")
                        st.rerun()

    elif page == "📅 Запись на сервис":
        st.title("ЗАПИСЬ В СЕРВИС")
        my_cars = get_client_cars(phone)
        if not my_cars:
            st.warning("Сначала добавьте автомобиль в гараж")
        else:
            with st.form("appointment_form"):
                sel_car = st.selectbox("Автомобиль", [f"{c['brand']} {c['model']}" for c in my_cars])
                svc_type = st.selectbox("Тип работ", ["ТО", "Ходовая часть", "Двигатель", "Электрика", "Диагностика"])
                req_date = st.date_input("Желаемая дата")
                req_time = st.time_input("Желаемое время")
                comment = st.text_area("Что случилось?")
                if st.form_submit_button("ЗАПИСАТЬСЯ"):
                    st.session_state.db_appointments.append({
                        'phone': phone, 'car': sel_car, 'service': svc_type, 
                        'date': str(req_date), 'time': str(req_time), 'comment': comment, 'status': 'Новая'
                    })
                    st.success("Заявка отправлена! Мы перезвоним вам для подтверждения.")

    elif page == "📋 История обслуживания":
        st.title("ИСТОРИЯ ОБСЛУЖИВАНИЯ")
        my_car_ids = [c['id'] for c in get_client_cars(phone)]
        history = get_car_history(my_car_ids)
        
        if not history:
            st.info("Вы еще не обслуживались у нас. После первого визита здесь появится список работ.")
        else:
            df_h = pd.DataFrame(history)
            car_map = {c['id']: f"{c['brand']} {c['model']} ({c['plate']})" for c in st.session_state.db_cars}
            df_h['Автомобиль'] = df_h['car_id'].map(car_map)
            st.dataframe(df_h[['id', 'date', 'Автомобиль', 'service', 'cost', 'status']].rename(columns={
                'id': '№ Заказа', 'date': 'Дата', 'service': 'Работы', 'cost': 'Сумма (₽)', 'status': 'Статус'
            }), hide_index=True, use_container_width=True)
            
    elif page == "❓ Справка":
        st.title("ПОМОЩЬ И УСТАНОВКА")
        st.markdown("""
        <div class='guide-box'>
            <h3>Как установить приложение на телефон?</h3>
            <p>Чтобы приложение «Автоспектр» всегда было под рукой, его можно добавить на главный экран смартфона:</p>
            <ol>
                <li>Откройте эту страницу в браузере <b>Google Chrome</b> на Android или <b>Safari</b> на iPhone.</li>
                <li>Нажмите кнопку <b>Меню</b> (три точки в Chrome справа сверху или квадрат со стрелкой вниз в Safari).</li>
                <li>Выберите пункт <b>«Добавить на главный экран»</b> или <b>«Установить приложение»</b>.</li>
            </ol>
            <p>Теперь иконка появится в меню вашего телефона!</p>
        </div>
        """, unsafe_allow_html=True)
        st.info("🔍 Ссылка, которую вы видите сейчас, является временной ссылкой разработчика. Для полноценной работы автосервиса на постоянной основе код должен быть размещен на сервере (например, Streamlit Cloud).")

# --- ПРОВЕРКА РОУТИНГА ---
if st.session_state.user_auth is None:
    show_auth()
elif st.session_state.user_auth == ADMIN_PHONE:
    show_admin()
else:
    show_client()
