import numpy as np

x = np.linspace(0, 2*np.pi, 500, endpoint=False, dtype=float)

fade = []

for i in x:
    fade.append(int(round(32767.5*np.sin(i+((3*np.pi)/2)) + 32767.5)))
    
print(fade)
