import pandas as pd
import os
import json
import psycopg2
from psycopg2.extras import execute_values
from openai import OpenAI
from dotenv import load_dotenv

load_dotenv()

# Konfigurasi Database
DB_HOST = os.getenv("DB_HOST")
DB_PORT = os.getenv("DB_PORT", 6543)
DB_NAME = os.getenv("DB_DATABASE")
DB_USER = os.getenv("DB_USERNAME")
DB_PASS = os.getenv("DB_PASSWORD")
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY") 
EXCEL_FILE = "dataset_beasiswa.xlsx"
CACHE_FILE = "data_berhasil_embedding.json"

# Setup AI
if OPENAI_API_KEY.startswith("sk-or"):
    client = OpenAI(api_key=OPENAI_API_KEY, base_url="https://openrouter.ai/api/v1")
    MODEL_NAME = "openai/text-embedding-3-small"
else:
    client = OpenAI(api_key=OPENAI_API_KEY)
    MODEL_NAME = "text-embedding-3-small"

def get_embedding(text):
    return client.embeddings.create(input=[text.replace("\n", " ")], model=MODEL_NAME).data[0].embedding

def find_column(df, possible_names):
    for name in possible_names:
        for col in df.columns:
            if col.strip().lower() == name.strip().lower(): return col
    return None

def main():
    # 1. CEK CACHE DULU (Biar hemat API)
    if os.path.exists(CACHE_FILE):
        print(f"📦 Menemukan cache {CACHE_FILE}. Menggunakan data yang sudah ada...")
        with open(CACHE_FILE, 'r') as f:
            data_to_insert = json.load(f)
    else:
        # Proses Embedding seperti biasa
        if not os.path.exists(EXCEL_FILE):
            print("❌ File Excel tidak ada!")
            return
        
        df = pd.read_excel(EXCEL_FILE)
        col_map = {
            'nama': find_column(df, ['nama beasiswa', 'nama', 'beasiswa']),
            'benua': find_column(df, ['benua']), 'negara': find_column(df, ['negara']),
            'jenjang': find_column(df, ['jenjang']), 'deskripsi': find_column(df, ['deskripsi']),
            'deadline': find_column(df, ['deadline']), 'kategori': find_column(df, ['kategori']),
            'jurusan': find_column(df, ['jurusan']), 'benefit': find_column(df, ['benefit']),
            'persyaratan': find_column(df, ['persyaratan']), 'sumber': find_column(df, ['sumber']),
            'url': find_column(df, ['url']), 'url_asli': find_column(df, ['url asli'])
        }

        data_to_insert = []
        for index, row in df.iterrows():
            nama = str(row[col_map['nama']])
            if not nama or nama == 'nan' or nama.strip() == '': continue
            print(f"[{index + 1}/{len(df)}] Embedding: {nama}")
            
            context = f"Beasiswa: {nama}\n"
            for key, col in col_map.items():
                if col and key != 'nama':
                    val = str(row[col])
                    if val != 'nan': context += f"{key.capitalize()}: {val}\n"
            
            try:
                embedding = get_embedding(context.strip())
                data_to_insert.append([
                    nama, str(row.get(col_map['benua'], '')), str(row.get(col_map['negara'], '')),
                    str(row.get(col_map['jenjang'], '')), str(row.get(col_map['deskripsi'], '')),
                    str(row.get(col_map['deadline'], '')), str(row.get(col_map['kategori'], '')),
                    str(row.get(col_map['jurusan'], '')), str(row.get(col_map['benefit'], '')),
                    str(row.get(col_map['persyaratan'], '')), str(row.get(col_map['sumber'], '')),
                    str(row.get(col_map['url'], '')), str(row.get(col_map['url_asli'], '')),
                    embedding
                ])
            except Exception as e:
                print(f"❌ Error row {index+1}: {e}")
        
        # Simpan ke JSON
        with open(CACHE_FILE, 'w') as f:
            json.dump(data_to_insert, f)
        print(f"💾 Data disimpan ke {CACHE_FILE}")

    # 2. UPLOAD KE DATABASE
    print(f" mencoba menghubungkan ke database {DB_HOST}...")
    try:
        # Gunakan format koneksi yang lebih lengkap
        conn = psycopg2.connect(
            host=DB_HOST,
            port=DB_PORT,
            database=DB_NAME,
            user=DB_USER,
            password=DB_PASS,
            connect_timeout=10
        )
        cur = conn.cursor()
        print("Clearing old data...")
        cur.execute("TRUNCATE TABLE scholarships;")
        
        query = """INSERT INTO scholarships (nama_beasiswa, benua, negara, jenjang, deskripsi, deadline, kategori, jurusan, benefit, persyaratan, sumber, url, url_asli, embedding) VALUES %s"""
        execute_values(cur, query, data_to_insert)
        conn.commit()
        print(f"✅ BERHASIL! {len(data_to_insert)} data masuk ke Supabase.")
        
        # Hapus cache kalau sudah sukses
        os.remove(CACHE_FILE)
        
        cur.close()
        conn.close()
    except Exception as e:
        print(f"\n❌ DATABASE MASIH ERROR: {e}")
        print("\n💡 SARAN:")
        print(f"1. Cek Dashboard Supabase -> Settings -> Database")
        print(f"2. Pastikan DB_HOST di .env sudah benar (biasanya formatnya: db.xxxx.supabase.co)")
        print(f"3. Jika kamu pakai Transaction Mode (Pooler), gunakan port 6543.")
        print(f"4. Tenang, data embedding kamu sudah aman di file '{CACHE_FILE}', tidak perlu bayar API lagi.")

if __name__ == "__main__":
    main()
