import os
import requests
from bs4 import BeautifulSoup
import unicodedata
from datetime import datetime
import json
import re

FORCE_WRITE = False

SCRAP_RESULTS_PATH = os.getenv('SCRAP_RESULTS_PATH', '/app/scrap-results')
MEDIA_JSON_PATH = '/app/scrap-results/medias.json'
MEDIA_DIR_PATH = '/app/scrap-results/medias'

def sanitize_filename(filename):
    filename = unicodedata.normalize('NFKD', filename).encode('ascii', 'ignore').decode('utf-8')
    
    caracteres_interdits = r'[?!@#$%^&*()<>{}[\]|/\\:;"]'
    filename = re.sub(caracteres_interdits, '', filename)
    
    filename = filename.lower().replace(' ', '-')
    
    filename = re.sub(r'-+', '-', filename)
    
    filename = filename.strip('-')
    
    return filename

def download_media():
    url = 'https://evilox.com/'
    try:
        response = requests.get(url, verify=False)
    except requests.exceptions.RequestException as e:
        print(f'HTTPS a échoué ({e}), tentative avec HTTP...')
        url = 'http://evilox.com/'
        try:
            response = requests.get(url)
        except requests.exceptions.RequestException as e:
            print(f'Erreur lors de la récupération de la page : {e}')
            return
        
    if response.status_code != 200:
        print(f'Erreur lors de la récupération de la page : {response.status_code}')
        return

    soup = BeautifulSoup(response.text, 'html.parser')
    cards = soup.find_all('div', class_='card mx-auto')
    print(f'Nombre de cartes trouvées : {len(cards)}')
    media_list = []

    if not os.path.exists(MEDIA_DIR_PATH):
        os.makedirs(MEDIA_DIR_PATH)
        print(f'Répertoire "{MEDIA_DIR_PATH}" créé.')

    for card in cards:
        title_element = card.find('h3', class_='card-title')
        if title_element:
            title = title_element.get_text(strip=True)
            print(f'Titre trouvé : {title}')
        else:
            print('Aucun titre trouvé pour cette carte.')
            continue
        
        media_url = card.find('img')['src'] if card.find('img') else card.find('source')['src'] if card.find('source') else None
        if media_url.startswith('/'):
            media_url = 'https://evilox.com' + media_url
            try:
                test_response = requests.get(media_url, verify=False)
                if test_response.status_code != 200:
                    media_url = 'http://evilox.com' + media_url
            except:
                media_url = 'http://evilox.com' + media_url
        
        print(f'URL du média : {media_url}')

        sanitized_title = sanitize_filename(title)

        if media_url.lower().endswith(('.png', '.jpg', '.jpeg', '.gif')):
            category = 'images'
        elif media_url.lower().endswith(('.mp4', '.avi', '.mov', '.mkv')):
            category = 'videos'
        else:
            category = 'autres'

        try:
            media_response = requests.get(media_url, verify=False)
            extension = media_url.split('.')[-1] if '.' in media_url else 'jpg'
            media_path = os.path.join(MEDIA_DIR_PATH, f'{sanitized_title}.{extension}')
            with open(media_path, 'wb') as media_file:
                media_file.write(media_response.content)
        except Exception as e:
            print(f'Erreur lors du téléchargement du média : {e}')
            continue

        today_date = datetime.now().strftime('%Y-%m-%d')
        description_element = card.find('p', class_='card-text')
        description = description_element.get_text(strip=True) if description_element else 'Aucune description'
        
        media_info = {
            "date": today_date,
            "title": title,
            "description": description,
            "category": category,
            "media_name": f"{sanitized_title}.{extension}"
        }
        media_list.append(media_info)
        print(f'Ajouté à la liste : {media_info}')

    if media_list:
        try:
            if os.path.exists(MEDIA_JSON_PATH):
                with open(MEDIA_JSON_PATH, 'r', encoding='utf-8') as json_file:
                    existing_data = json.load(json_file)
                    print(f'Données existantes lues : {existing_data}')

                last_date = existing_data[-1]['date'] if existing_data else None
                print(f'Dernière date enregistrée : {last_date}')

                if last_date == today_date and not FORCE_WRITE:
                    print('Aucune nouvelle donnée à écrire, la dernière date est la date du jour.')
                    return
            else:
                existing_data = []

            existing_data.extend(media_list)
            with open(MEDIA_JSON_PATH, 'w', encoding='utf-8') as json_file:
                json.dump(existing_data, json_file, ensure_ascii=False, indent=4)
            print('Données écrites dans', MEDIA_JSON_PATH)
        except Exception as e:
            print(f'Erreur lors de l\'écriture dans {MEDIA_JSON_PATH} : {e}')
    else:
        print('Aucune donnée à écrire dans', MEDIA_JSON_PATH)

download_media()
