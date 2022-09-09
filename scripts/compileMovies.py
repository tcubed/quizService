# -*- coding: utf-8 -*-
"""
Created on Thu Sep  8 21:12:45 2022

@author: Ted
"""

import pandas as pd

fnxlsx='Movie_questions.xlsx'
df=pd.read_excel(fnxlsx)
# %%
fnjson='movie_db.json'
df.to_json(fnjson,indent=2,orient='records')