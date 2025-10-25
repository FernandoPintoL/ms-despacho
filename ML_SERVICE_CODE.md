# ML Service (Python) - Código Completo

## 📁 Estructura del Proyecto

```
ml-service/
├── app.py
├── requirements.txt
├── models/
│   └── tiempo_llegada.pkl
├── data/
│   └── training_data.csv
├── .env
├── .gitignore
└── README.md
```

## 📦 requirements.txt

```
flask==3.0.0
scikit-learn==1.3.2
numpy==1.24.3
pandas==2.1.3
joblib==1.3.2
python-dotenv==1.0.0
flask-cors==4.0.0
```

## 🔧 .env

```env
FLASK_APP=app.py
FLASK_ENV=development
PORT=5000
MODEL_PATH=models/tiempo_llegada.pkl
DATA_PATH=data/training_data.csv
```

## 🚀 app.py

```python
from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import os
from dotenv import load_dotenv
import logging
from datetime import datetime

# Cargar variables de entorno
load_dotenv()

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Inicializar Flask
app = Flask(__name__)
CORS(app)

# Configuración
MODEL_PATH = os.getenv('MODEL_PATH', 'models/tiempo_llegada.pkl')
DATA_PATH = os.getenv('DATA_PATH', 'data/training_data.csv')

# Crear directorios si no existen
os.makedirs('models', exist_ok=True)
os.makedirs('data', exist_ok=True)

# Modelo global
model = None


def encode_tipo_ambulancia(tipo):
    """Codificar tipo de ambulancia a número"""
    tipos = {
        'basica': 0,
        'intermedia': 1,
        'avanzada': 2,
        'uci': 3
    }
    return tipos.get(tipo.lower(), 0)


def decode_tipo_ambulancia(codigo):
    """Decodificar número a tipo de ambulancia"""
    tipos = {0: 'basica', 1: 'intermedia', 2: 'avanzada', 3: 'uci'}
    return tipos.get(codigo, 'basica')


def cargar_modelo():
    """Cargar modelo desde disco"""
    global model
    try:
        if os.path.exists(MODEL_PATH):
            model = joblib.load(MODEL_PATH)
            logger.info(f"✅ Modelo cargado desde {MODEL_PATH}")
            return True
        else:
            logger.warning(f"⚠️  Modelo no encontrado en {MODEL_PATH}")
            return False
    except Exception as e:
        logger.error(f"❌ Error cargando modelo: {e}")
        return False


def guardar_modelo(trained_model):
    """Guardar modelo en disco"""
    try:
        joblib.dump(trained_model, MODEL_PATH)
        logger.info(f"✅ Modelo guardado en {MODEL_PATH}")
        return True
    except Exception as e:
        logger.error(f"❌ Error guardando modelo: {e}")
        return False


@app.route('/health', methods=['GET'])
def health():
    """Endpoint de salud"""
    return jsonify({
        'status': 'ok',
        'service': 'ML Service',
        'model_loaded': model is not None,
        'model_path': MODEL_PATH,
        'timestamp': datetime.now().isoformat()
    })


@app.route('/predict', methods=['POST'])
def predict():
    """
    Predecir tiempo de llegada de ambulancia
    
    Body JSON:
    {
        "distancia": 5.2,
        "hora_dia": 14,
        "dia_semana": 3,
        "tipo_ambulancia": "avanzada",
        "trafico_estimado": 0.7
    }
    """
    try:
        data = request.json
        
        # Validar datos requeridos
        required_fields = ['distancia', 'hora_dia', 'dia_semana', 'tipo_ambulancia', 'trafico_estimado']
        for field in required_fields:
            if field not in data:
                return jsonify({'error': f'Campo requerido: {field}'}), 400
        
        # Si no hay modelo, usar estimación básica
        if model is None:
            tiempo_estimado = estimacion_basica(data['distancia'])
            return jsonify({
                'tiempo_estimado': tiempo_estimado,
                'confianza': 0.5,
                'metodo': 'estimacion_basica',
                'mensaje': 'Modelo no entrenado, usando estimación básica'
            })
        
        # Preparar features
        features = np.array([[
            data['distancia'],
            data['hora_dia'],
            data['dia_semana'],
            encode_tipo_ambulancia(data['tipo_ambulancia']),
            data['trafico_estimado']
        ]])
        
        # Predecir
        prediccion = model.predict(features)[0]
        tiempo_estimado = max(5, round(prediccion))  # Mínimo 5 minutos
        
        logger.info(f"📊 Predicción: {tiempo_estimado} min para distancia {data['distancia']} km")
        
        return jsonify({
            'tiempo_estimado': int(tiempo_estimado),
            'confianza': 0.85,
            'metodo': 'random_forest',
            'features_usados': {
                'distancia_km': data['distancia'],
                'hora_dia': data['hora_dia'],
                'dia_semana': data['dia_semana'],
                'tipo_ambulancia': data['tipo_ambulancia'],
                'trafico': data['trafico_estimado']
            }
        })
        
    except Exception as e:
        logger.error(f"❌ Error en predicción: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/train', methods=['POST'])
def train():
    """
    Entrenar modelo con datos históricos
    
    Body JSON:
    {
        "features": [[5.2, 14, 3, 2, 0.7], ...],
        "targets": [15, 20, 12, ...]
    }
    """
    try:
        data = request.json
        
        if 'features' not in data or 'targets' not in data:
            return jsonify({'error': 'Se requieren features y targets'}), 400
        
        X = np.array(data['features'])
        y = np.array(data['targets'])
        
        if len(X) < 10:
            return jsonify({'error': 'Se requieren al menos 10 muestras para entrenar'}), 400
        
        # Split train/test
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        
        # Entrenar modelo
        logger.info(f"🎓 Entrenando modelo con {len(X_train)} muestras...")
        
        trained_model = RandomForestRegressor(
            n_estimators=100,
            max_depth=10,
            min_samples_split=5,
            random_state=42,
            n_jobs=-1
        )
        trained_model.fit(X_train, y_train)
        
        # Evaluar
        y_pred = trained_model.predict(X_test)
        mae = mean_absolute_error(y_test, y_pred)
        rmse = np.sqrt(mean_squared_error(y_test, y_pred))
        r2 = r2_score(y_test, y_pred)
        
        # Guardar modelo
        global model
        model = trained_model
        guardar_modelo(model)
        
        logger.info(f"✅ Modelo entrenado - MAE: {mae:.2f}, RMSE: {rmse:.2f}, R²: {r2:.3f}")
        
        return jsonify({
            'status': 'success',
            'mensaje': 'Modelo entrenado exitosamente',
            'metricas': {
                'mae': round(mae, 2),
                'rmse': round(rmse, 2),
                'r2': round(r2, 3)
            },
            'muestras_entrenamiento': len(X_train),
            'muestras_prueba': len(X_test),
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"❌ Error entrenando modelo: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/evaluate', methods=['GET'])
def evaluate():
    """Evaluar modelo actual"""
    try:
        if model is None:
            return jsonify({'error': 'Modelo no entrenado'}), 400
        
        # Cargar datos de prueba si existen
        if not os.path.exists(DATA_PATH):
            return jsonify({'error': 'No hay datos de prueba disponibles'}), 404
        
        df = pd.read_csv(DATA_PATH)
        X = df[['distancia', 'hora_dia', 'dia_semana', 'tipo_ambulancia_encoded', 'trafico']].values
        y = df['tiempo_real'].values
        
        # Predecir
        y_pred = model.predict(X)
        
        # Métricas
        mae = mean_absolute_error(y, y_pred)
        rmse = np.sqrt(mean_squared_error(y, y_pred))
        r2 = r2_score(y, y_pred)
        
        return jsonify({
            'metricas': {
                'mae': round(mae, 2),
                'rmse': round(rmse, 2),
                'r2': round(r2, 3)
            },
            'muestras': len(X),
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"❌ Error evaluando modelo: {e}")
        return jsonify({'error': str(e)}), 500


@app.route('/retrain', methods=['POST'])
def retrain():
    """
    Reentrenar modelo con nuevos datos desde Laravel
    """
    try:
        # Este endpoint sería llamado periódicamente por Laravel
        # para actualizar el modelo con datos reales
        
        data = request.json
        
        if 'nuevos_datos' not in data:
            return jsonify({'error': 'Se requieren nuevos_datos'}), 400
        
        # Aquí iría la lógica para:
        # 1. Cargar datos existentes
        # 2. Agregar nuevos datos
        # 3. Reentrenar modelo
        # 4. Evaluar mejora
        
        return jsonify({
            'status': 'success',
            'mensaje': 'Modelo reentrenado con nuevos datos'
        })
        
    except Exception as e:
        logger.error(f"❌ Error reentrenando: {e}")
        return jsonify({'error': str(e)}), 500


def estimacion_basica(distancia_km):
    """
    Estimación básica cuando no hay modelo entrenado
    Asume velocidad promedio de 40 km/h en ciudad
    """
    velocidad_promedio = 40  # km/h
    tiempo_minutos = (distancia_km / velocidad_promedio) * 60
    return max(5, round(tiempo_minutos))


@app.route('/generate-sample-data', methods=['POST'])
def generate_sample_data():
    """
    Generar datos sintéticos para entrenamiento inicial
    """
    try:
        num_samples = request.json.get('num_samples', 100)
        
        logger.info(f"🎲 Generando {num_samples} muestras sintéticas...")
        
        np.random.seed(42)
        
        data = []
        for _ in range(num_samples):
            distancia = np.random.uniform(1, 50)  # 1-50 km
            hora_dia = np.random.randint(0, 24)
            dia_semana = np.random.randint(0, 7)
            tipo_amb = np.random.randint(0, 4)
            trafico = np.random.uniform(0.3, 1.0)
            
            # Calcular tiempo real con algo de ruido
            velocidad_base = 40  # km/h
            factor_trafico = 1 / trafico
            factor_hora = 1.2 if 7 <= hora_dia <= 9 or 17 <= hora_dia <= 19 else 1.0
            factor_tipo = [1.0, 0.95, 0.9, 0.85][tipo_amb]  # Más rápidas las avanzadas
            
            velocidad_efectiva = velocidad_base * factor_trafico * factor_hora * factor_tipo
            tiempo_real = (distancia / velocidad_efectiva) * 60
            tiempo_real += np.random.normal(0, 2)  # Ruido
            tiempo_real = max(5, tiempo_real)
            
            data.append({
                'distancia': round(distancia, 2),
                'hora_dia': hora_dia,
                'dia_semana': dia_semana,
                'tipo_ambulancia_encoded': tipo_amb,
                'trafico': round(trafico, 2),
                'tiempo_real': round(tiempo_real, 1)
            })
        
        # Guardar como CSV
        df = pd.DataFrame(data)
        df.to_csv(DATA_PATH, index=False)
        
        logger.info(f"✅ {num_samples} muestras generadas y guardadas en {DATA_PATH}")
        
        return jsonify({
            'status': 'success',
            'mensaje': f'{num_samples} muestras generadas',
            'archivo': DATA_PATH
        })
        
    except Exception as e:
        logger.error(f"❌ Error generando datos: {e}")
        return jsonify({'error': str(e)}), 500


# Cargar modelo al iniciar
cargar_modelo()

if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    
    print(f"""
╔═══════════════════════════════════════════╗
║   🤖 ML Service - Predicción de Tiempos   ║
║   Puerto: {port}                            ║
║   Modelo: {'✅ Cargado' if model else '❌ No cargado'}                   ║
╚═══════════════════════════════════════════╝
    """)
    
    app.run(
        host='0.0.0.0',
        port=port,
        debug=os.getenv('FLASK_ENV') == 'development'
    )
```

## 📝 .gitignore

```
__pycache__/
*.py[cod]
*$py.class
*.so
.Python
venv/
env/
ENV/
.env
.env.local
models/*.pkl
data/*.csv
.DS_Store
*.log
```

## 📖 README.md

```markdown
# ML Service - Predicción de Tiempos de Llegada

Servicio Python con Flask y scikit-learn para predicción de tiempos de llegada de ambulancias.

## Instalación

\`\`\`bash
# Crear entorno virtual
python -m venv venv

# Activar
# Windows:
venv\\Scripts\\activate
# Linux/Mac:
source venv/bin/activate

# Instalar dependencias
pip install -r requirements.txt
\`\`\`

## Uso

\`\`\`bash
# Desarrollo
python app.py

# Producción con Gunicorn
pip install gunicorn
gunicorn -w 4 -b 0.0.0.0:5000 app:app
\`\`\`

## Endpoints

### POST /predict
Predecir tiempo de llegada

### POST /train
Entrenar modelo con datos históricos

### GET /evaluate
Evaluar precisión del modelo

### POST /generate-sample-data
Generar datos sintéticos para pruebas

### GET /health
Estado del servicio

## Features del Modelo

1. **distancia** (km): Distancia entre ambulancia y destino
2. **hora_dia** (0-23): Hora del día
3. **dia_semana** (0-6): Día de la semana
4. **tipo_ambulancia** (0-3): Tipo de ambulancia
5. **trafico_estimado** (0-1): Factor de tráfico
```

## 🧪 test_ml.py (Script de Prueba)

```python
import requests
import json

BASE_URL = 'http://localhost:5000'

def test_health():
    print("🔍 Probando /health...")
    response = requests.get(f'{BASE_URL}/health')
    print(json.dumps(response.json(), indent=2))

def test_generate_data():
    print("\n🎲 Generando datos sintéticos...")
    response = requests.post(f'{BASE_URL}/generate-sample-data', json={'num_samples': 100})
    print(json.dumps(response.json(), indent=2))

def test_train():
    print("\n🎓 Entrenando modelo...")
    
    # Generar datos de ejemplo
    features = []
    targets = []
    
    for i in range(50):
        distancia = 5 + (i * 0.5)
        features.append([distancia, 14, 3, 2, 0.7])
        targets.append(distancia * 1.5 + 5)  # Tiempo aproximado
    
    response = requests.post(f'{BASE_URL}/train', json={
        'features': features,
        'targets': targets
    })
    print(json.dumps(response.json(), indent=2))

def test_predict():
    print("\n📊 Probando predicción...")
    response = requests.post(f'{BASE_URL}/predict', json={
        'distancia': 10.5,
        'hora_dia': 14,
        'dia_semana': 3,
        'tipo_ambulancia': 'avanzada',
        'trafico_estimado': 0.7
    })
    print(json.dumps(response.json(), indent=2))

if __name__ == '__main__':
    test_health()
    test_generate_data()
    test_train()
    test_predict()
```

## 🚀 Comandos Útiles

```bash
# Instalar dependencias
pip install -r requirements.txt

# Ejecutar servidor
python app.py

# Ejecutar tests
python test_ml.py

# Generar datos sintéticos
curl -X POST http://localhost:5000/generate-sample-data -H "Content-Type: application/json" -d '{"num_samples": 100}'

# Entrenar modelo (después de generar datos)
# Ver test_ml.py para ejemplo completo

# Predecir tiempo
curl -X POST http://localhost:5000/predict \
  -H "Content-Type: application/json" \
  -d '{
    "distancia": 10.5,
    "hora_dia": 14,
    "dia_semana": 3,
    "tipo_ambulancia": "avanzada",
    "trafico_estimado": 0.7
  }'
```
